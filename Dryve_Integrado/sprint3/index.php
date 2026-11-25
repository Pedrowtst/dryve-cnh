<?php
require_once 'dados.php';
require_once 'pagamentos.php';
exigir_conta();
$id = usuario_id();
$aluno = perfil_atual() === 'aluno';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    try {
        $conn->begin_transaction();
        $a = linha('SELECT g.*, h.instrutor_id, h.inicio, a.status AS situacao
            FROM agendamentos g JOIN horarios h ON h.id = g.horario_id
            LEFT JOIN s4_aulas a ON a.id = g.aula_id
            WHERE g.id = ? AND (g.aluno_id = ? OR h.instrutor_id = ?) FOR UPDATE', [(int) campo('id'), $id, $id]);
        if (!$a || in_array($a['situacao'], ['concluida', 'em_andamento', 'cancelada'], true) ||
            !$a['reserva'] || !in_array($a['status'], ['pendente', 'aceito', 'reagendamento'], true)) {
            throw new DomainException('Este agendamento não pode mais ser alterado.');
        }
        $acao = campo('acao');
        $mensagem = 'Agendamento atualizado.';
        $link = null;
        if (in_array($acao, ['aceitar', 'recusar'], true) && (int) $a['responder_id'] === $id && $a['status'] !== 'aceito') {
            if ($acao === 'aceitar') {
                $novoHorario = $a['proposta_id'] ?: $a['horario_id'];
                $h = $a['proposta_id'] ? horario_livre($novoHorario) : linha('SELECT * FROM horarios WHERE id = ? AND inicio > NOW() FOR UPDATE', [$novoHorario]);
                if (!$h || (int) $h['instrutor_id'] !== (int) $a['instrutor_id']) {
                    throw new DomainException('Esse horário não está mais disponível.');
                }
                verificar_horario_aluno($a['aluno_id'], $h['inicio'], $a['id']);
                gravar_aula($a, $h);
                consulta("UPDATE agendamentos SET status = 'aceito', horario_id = ?, proposta_id = NULL WHERE id = ?", [$novoHorario, $a['id']]);
                $mensagem = 'Horário confirmado. A aula já aparece em Acompanhamento.';
            } elseif ($a['status'] === 'reagendamento') {
                consulta("UPDATE agendamentos SET status = 'aceito', proposta_id = NULL WHERE id = ?", [$a['id']]);
                $mensagem = 'Proposta recusada. O horário original continua confirmado.';
            } else {
                consulta("UPDATE agendamentos SET status = 'recusado', reserva = NULL WHERE id = ?", [$a['id']]);
                $mensagem = 'Solicitação recusada. O horário voltou para a grade.';
            }
        } elseif ($acao === 'cancelar') {
            estornar_pagamento($a);
            consulta("UPDATE agendamentos SET status = 'cancelado', reserva = NULL, proposta_id = NULL WHERE id = ?", [$a['id']]);
            if ($a['aula_id']) {
                consulta("UPDATE s4_aulas SET status = 'cancelada', compartilhando = 0,
                    ultima_lat = NULL, ultima_lng = NULL, ultima_localizacao_em = NULL WHERE id = ?", [$a['aula_id']]);
            }
            $mensagem = 'Agendamento cancelado.';
        } elseif ($acao === 'propor' && $a['status'] === 'aceito') {
            $h = horario_livre((int) campo('novo_horario'));
            if ((int) $h['instrutor_id'] !== (int) $a['instrutor_id'] || !in_array($h['categoria'], [$a['categoria'], 'A/B'], true)) {
                throw new DomainException('Escolha outro horário do mesmo instrutor e categoria.');
            }
            $responder = $aluno ? $a['instrutor_id'] : $a['aluno_id'];
            consulta("UPDATE agendamentos SET status = 'reagendamento', proposta_id = ?, responder_id = ? WHERE id = ?",
                [$h['id'], $responder, $a['id']]);
            $mensagem = 'Proposta enviada. O horário original vale até o novo ser aceito.';
        } elseif (in_array($acao, ['pagar', 'confirmar'], true) && $aluno && $a['aula_id'] &&
            ($acao === 'confirmar' || strtotime($a['inicio']) > time())) {
            if (linha("SELECT id FROM transacoes WHERE agendamento_id = ? AND tipo = 'pagamento'", [$a['id']])) {
                throw new DomainException('Este agendamento já foi pago.');
            }
            if ($acao === 'confirmar') {
                $checkout = checkout_aberto($a);
                if (!$checkout || !registrar_pagamento_stripe($a, stripe('checkout/sessions/' . rawurlencode($checkout['sessao'])))) {
                    throw new DomainException('O pagamento ainda não foi confirmado pelo serviço de teste.');
                }
            } elseif (stripe_ativo()) {
                $link = iniciar_checkout($a);
            } else {
                $metodo = campo('metodo');
                if (checkout_aberto($a) || !in_array($metodo, ['Pix simulado', 'Cartão simulado'], true)) {
                    throw new DomainException('Forma de teste inválida.');
                }
                consulta("INSERT INTO transacoes (agendamento_id, tipo, valor, metodo)
                    VALUES (?, 'pagamento', ?, ?)", [$a['id'], total_agendamento($a), $metodo]);
            }
            $mensagem = 'Pagamento de teste registrado.';
        } else {
            throw new DomainException('Essa ação não está disponível para este agendamento.');
        }
        $conn->commit();
        if ($link) {
            header('Location: ' . $link, true, 303);
            exit;
        }
        redirecionar('index.php', $mensagem);
    } catch (DomainException $excecao) {
        $conn->rollback();
        $erro = $excecao->getMessage();
    } catch (mysqli_sql_exception $excecao) {
        $conn->rollback();
        $erro = 'Não foi possível atualizar. Confira os dados e tente novamente.';
    }
}

$lista = linhas("SELECT g.*, h.instrutor_id, h.inicio, h.ponto_nome, h.destino_nome,
    p.inicio AS proposta, a.status AS situacao, t.id AS pago, c.id AS checkout,
    CONCAT(u.nome, ' ', u.sobrenome) AS outra_pessoa
    FROM agendamentos g JOIN horarios h ON h.id = g.horario_id
    JOIN usuarios u ON u.id = IF(h.instrutor_id = ?, g.aluno_id, h.instrutor_id)
    LEFT JOIN s4_aulas a ON a.id = g.aula_id LEFT JOIN horarios p ON p.id = g.proposta_id
    LEFT JOIN transacoes t ON t.agendamento_id = g.id AND t.tipo = 'pagamento'
    LEFT JOIN checkout_testes c ON c.agendamento_id = g.id
    WHERE g.aluno_id = ? OR h.instrutor_id = ? ORDER BY h.inicio DESC", [$id, $id, $id]);
$status = ['pendente' => 'Aguardando aceite', 'aceito' => 'Aceito', 'recusado' => 'Recusado',
    'cancelado' => 'Cancelado', 'reagendamento' => 'Reagendamento proposto',
    'em_andamento' => 'Em andamento', 'concluida' => 'Concluída'];
$titulo = 'Agendamentos';
$modulo = true;
require '../sprint1/header.php';
?>
<section class="modulo">
    <h1>Meus agendamentos</h1>
    <?php if ($aluno): ?><p><a class="botao" href="../sprint2/index.php">Procurar horário</a> <a href="../sprint6/index.php#transacoes">Histórico de pagamentos</a></p><?php endif; ?>
    <?php avisos_pagina($erro); ?>
    <?php if (isset($_GET['retorno'])): ?><p>Use “Confirmar pagamento de teste” no agendamento para consultar o resultado.</p><?php endif; ?>
    <?php if (!$lista): ?><p>Você ainda não tem agendamentos.</p><?php endif; ?>
    <?php foreach ($lista as $a):
        $situacao = in_array($a['situacao'], ['concluida', 'em_andamento'], true) ? $a['situacao'] : $a['status'];
        $alteravel = $a['reserva'] && in_array($situacao, ['pendente', 'aceito', 'reagendamento'], true);
        $responder = $alteravel && (int) $a['responder_id'] === $id && $a['status'] !== 'aceito';
        $alternativas = $situacao === 'aceito' ? linhas('SELECT h.id, h.inicio, h.ponto_nome
            FROM horarios h LEFT JOIN agendamentos g ON g.horario_id = h.id AND g.reserva = 1
            WHERE h.instrutor_id = ? AND h.inicio > NOW() AND h.disponivel = 1 AND g.id IS NULL ORDER BY h.inicio',
            [$a['instrutor_id']]) : [];
    ?>
    <article class="bloco">
        <h2>Aula #<?= (int) $a['id'] ?> · <?= e($a['outra_pessoa']) ?></h2>
        <p class="indicador"><?= e($status[$situacao]) ?></p>
        <p><?= dia_hora($a['inicio']) ?> · Categoria <?= e($a['categoria']) ?></p>
        <p><?= e($a['ponto_nome']) ?> → <?= e($a['destino_nome']) ?></p>
        <?php if ($a['proposta']): ?><p>Novo horário proposto: <?= dia_hora($a['proposta']) ?></p><?php endif; ?>
        <p>Total <?= reais(total_agendamento($a)) ?><?php if ($a['cupom']): ?> · Cupom <?= e($a['cupom']) ?><?php endif; ?>
            <?php if ($a['pago']): ?> · <?= $situacao === 'cancelado' ? 'Estornado' : 'Pago em teste' ?>
            <?php elseif (in_array($situacao, ['aceito', 'reagendamento'], true)): ?> · Pagamento pendente<?php endif; ?></p>
        <?php if ($a['aula_id'] && $situacao !== 'cancelado'): ?>
            <p><a href="../sprint4/index.html?aula=<?= (int) $a['aula_id'] ?>">Abrir acompanhamento</a>
            <?php if ($situacao === 'concluida'): ?> · <a href="../sprint6/aula.php?id=<?= (int) $a['aula_id'] ?>">Ver pós-aula</a><?php endif; ?></p>
        <?php endif; ?>
        <?php if ($alteravel): ?>
            <form method="post" class="acoes"><?php campo_csrf(); ?><input type="hidden" name="id" value="<?= $a['id'] ?>">
                <?php if ($responder): ?><button class="botao" name="acao" value="aceitar">Aceitar</button>
                    <button class="botao secundario" name="acao" value="recusar">Recusar</button><?php endif; ?>
                <button class="botao secundario" name="acao" value="cancelar">Cancelar aula</button>
            </form>
        <?php endif; ?>
        <?php if ($aluno && $alteravel && $a['aula_id'] && !$a['pago'] && ($a['checkout'] || strtotime($a['inicio']) > time())): ?>
            <form method="post" class="acoes"><?php campo_csrf(); ?><input type="hidden" name="id" value="<?= $a['id'] ?>">
                <?php if (!stripe_ativo() && !$a['checkout']): ?>
                    <label>Forma de teste <select name="metodo"><option>Pix simulado</option><option>Cartão simulado</option></select></label>
                <?php endif; ?>
                <?php if (strtotime($a['inicio']) > time() && (stripe_ativo() || !$a['checkout'])): ?>
                    <button class="botao" name="acao" value="pagar">Pagar em teste</button>
                <?php endif; ?>
                <?php if ($a['checkout']): ?><button class="botao secundario" name="acao" value="confirmar">Confirmar pagamento de teste</button><?php endif; ?>
            </form>
        <?php endif; ?>
        <?php if ($alternativas): ?>
            <details><summary>Propor novo horário</summary><form method="post" class="acoes">
                <?php campo_csrf(); ?><input type="hidden" name="id" value="<?= $a['id'] ?>">
                <label>Alternativas <select name="novo_horario">
                    <?php foreach ($alternativas as $h): ?><option value="<?= $h['id'] ?>"><?= dia_hora($h['inicio']) ?> · <?= e($h['ponto_nome']) ?></option><?php endforeach; ?>
                </select></label>
                <button class="botao" name="acao" value="propor">Propor reagendamento</button>
            </form></details>
        <?php endif; ?>
    </article>
    <?php endforeach; ?>
</section>
<?php require '../sprint1/footer.php'; ?>
