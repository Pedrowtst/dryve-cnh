<?php
require_once '../app.php';
exigir_conta();
$id = usuario_id();
$aluno = perfil_atual() === 'aluno';
$aulas = linhas("SELECT a.*, CONCAT(u.nome, ' ', u.sobrenome) AS outra_pessoa, v.nota, o.desempenho
    FROM s4_aulas a JOIN usuarios u ON u.id = IF(a.aluno_id = ?, a.instrutor_id, a.aluno_id)
    LEFT JOIN avaliacoes v ON v.aula_id = a.id LEFT JOIN observacoes o ON o.aula_id = a.id
    WHERE a.aluno_id = ? OR a.instrutor_id = ? ORDER BY a.inicio DESC", [$id, $id, $id]);
if ($aluno) {
    $modulos = linhas('SELECT m.titulo, p.concluido_em FROM modulos m
        LEFT JOIN progresso p ON p.modulo_id = m.id AND p.aluno_id = ? ORDER BY m.id', [$id]);
    $concluidos = count(array_filter($modulos, fn($m) => $m['concluido_em']));
    $percentual = $modulos ? (int) round(100 * $concluidos / count($modulos)) : 0;
    $temAula = in_array('concluida', array_column($aulas, 'status'), true);
    $comprovante = linha('SELECT codigo FROM comprovantes WHERE aluno_id = ?', [$id]);
    $transacoes = linhas('SELECT t.*, a.id AS pedido FROM transacoes t
        JOIN agendamentos a ON a.id = t.agendamento_id WHERE a.aluno_id = ? ORDER BY t.id DESC', [$id]);
}
$situacoes = ['agendada' => 'Agendada', 'em_andamento' => 'Em andamento', 'concluida' => 'Concluída', 'cancelada' => 'Cancelada'];
$titulo = $aluno ? 'Meu progresso' : 'Histórico de aulas';
$modulo = true;
require '../sprint1/header.php';
?>
<section class="modulo">
    <h1><?= e($titulo) ?></h1>
    <?php avisos_pagina(); ?>
    <?php if ($aluno): ?>
    <div class="bloco">
        <h2>Módulos de estudo</h2>
        <p><strong><?= $percentual ?>%</strong> · <?= $concluidos ?> de <?= count($modulos) ?> módulos concluídos</p>
        <progress value="<?= $percentual ?>" max="100" aria-label="Percentual de módulos concluídos"><?= $percentual ?>%</progress>
        <ul><?php foreach ($modulos as $m): ?>
            <li><?= e($m['titulo']) ?> · <?= $m['concluido_em'] ? 'Concluído' : 'Pendente' ?></li>
        <?php endforeach; ?></ul>
        <a href="../sprint5/index.php">Abrir estudos</a>
    </div>
    <div class="bloco">
        <h2>Comprovante de conclusão</h2>
        <?php if ($comprovante): ?>
            <a class="botao" href="comprovante.php">Abrir comprovante digital</a>
        <?php elseif ($modulos && $concluidos === count($modulos) && $temAula): ?>
            <form method="post" action="comprovante.php"><?php campo_csrf(); ?><button class="botao">Emitir comprovante</button></form>
        <?php else: ?>
            <p>Disponível após concluir todos os módulos de estudo e pelo menos uma aula prática.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="bloco" id="aulas">
        <h2>Histórico de aulas</h2>
        <?php if (!$aulas): ?><p>Nenhuma aula confirmada no histórico.</p><?php endif; ?>
        <?php foreach ($aulas as $a): ?>
            <div class="linha">
                <h3><?= e($a['outra_pessoa']) ?> · <?= e($situacoes[$a['status']]) ?></h3>
                <p><?= dia_hora($a['inicio']) ?> · <?= e($a['ponto_nome']) ?> → <?= e($a['destino_nome']) ?></p>
                <?php if ($a['status'] === 'concluida'): ?>
                    <p><?= $a['nota'] ? e($a['nota']) . '/5 estrelas' : 'Sem avaliação' ?> ·
                        <?= $a['desempenho'] ? 'Observação registrada' : 'Sem observação pedagógica' ?></p>
                    <a href="aula.php?id=<?= (int) $a['id'] ?>">Ver pós-aula</a>
                <?php elseif ($a['status'] !== 'cancelada'): ?>
                    <a href="../sprint4/index.html?aula=<?= (int) $a['id'] ?>">Acompanhar aula</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($aluno): ?>
    <div class="bloco" id="transacoes">
        <h2>Histórico de transações</h2>
        <?php if (!$transacoes): ?><p>Nenhuma transação registrada.</p><?php endif; ?>
        <?php foreach ($transacoes as $t): ?>
        <div class="linha">
            <h3><?= $t['tipo'] === 'estorno' ? 'Estorno' : 'Pagamento' ?> · <?= reais($t['valor']) ?></h3>
            <p>Aula #<?= (int) $t['pedido'] ?> · <?= e($t['metodo']) ?> · <?= dia_hora($t['criada_em']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php require '../sprint1/footer.php'; ?>
