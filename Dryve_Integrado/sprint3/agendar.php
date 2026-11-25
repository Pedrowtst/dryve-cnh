<?php
require_once 'dados.php';
$id = filter_input(INPUT_GET, 'horario', FILTER_VALIDATE_INT) ?: 0;
if (!usuario_id() && $id > 0 && $_SERVER['REQUEST_METHOD'] === 'GET') $_SESSION['horario_login'] = $id;
exigir_conta('aluno');
$erro = '';
$h = linha("SELECT h.*, i.preco, i.categoria, CONCAT(u.nome, ' ', u.sobrenome) AS nome
    FROM horarios h JOIN instrutores i ON i.usuario_id = h.instrutor_id JOIN usuarios u ON u.id = h.instrutor_id
    LEFT JOIN agendamentos a ON a.horario_id = h.id AND a.reserva = 1
    WHERE h.id = ? AND h.inicio > NOW() AND h.disponivel = 1 AND a.id IS NULL AND u.perfil = 'instrutor'", [$id]);
$cupom = '';
$categoria = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $cupom = strtoupper(campo('cupom'));
    $categoria = campo('categoria');
    try {
        $conn->begin_transaction();
        $h = horario_livre($id);
        verificar_horario_aluno(usuario_id(), $h['inicio']);
        if (!in_array($categoria, ['A', 'B'], true) || ($h['categoria'] !== 'A/B' && $h['categoria'] !== $categoria)) {
            throw new DomainException('Selecione uma categoria oferecida pelo instrutor.');
        }
        if ($cupom !== '' && !isset(CUPONS[$cupom])) {
            throw new DomainException('Cupom inválido.');
        }
        $desconto = round($h['preco'] * (CUPONS[$cupom] ?? 0) / 100, 2);
        consulta('INSERT INTO agendamentos (aluno_id, horario_id, responder_id, categoria, valor, desconto, cupom)
            VALUES (?, ?, ?, ?, ?, ?, ?)',
            [usuario_id(), $id, $h['instrutor_id'], $categoria, $h['preco'], $desconto, $cupom ?: null]);
        $conn->commit();
        redirecionar('index.php', 'Solicitação enviada. O pagamento fica disponível após o aceite.');
    } catch (DomainException $excecao) {
        $conn->rollback();
        $erro = $excecao->getMessage();
    } catch (mysqli_sql_exception $excecao) {
        $conn->rollback();
        $erro = 'Não foi possível reservar. Confira a disponibilidade e tente novamente.';
    }
}
$titulo = 'Solicitar aula';
$modulo = true;
require '../sprint1/header.php';
?>
<section class="modulo">
    <h1>Solicitar aula prática</h1>
    <?php avisos_pagina($erro); ?>
    <?php if (!$h): ?><p>Este horário não está disponível.</p><a href="../sprint2/index.php">Ver outros horários</a>
    <?php else: ?>
    <form method="post" class="bloco" id="checkout" data-valor="<?= e($h['preco']) ?>" data-cupons="<?= e(json_encode(CUPONS)) ?>">
        <?php campo_csrf(); ?>
        <h2><?= e($h['nome']) ?></h2>
        <p><?= dia_hora($h['inicio']) ?> · <?= e($h['ponto_nome']) ?> → <?= e($h['destino_nome']) ?></p>
        <div class="campos">
            <label>Categoria <select name="categoria">
                <?php foreach (explode('/', $h['categoria']) as $opcao): ?><option <?= $categoria === $opcao ? 'selected' : '' ?>><?= e($opcao) ?></option><?php endforeach; ?>
            </select></label>
            <label>Cupom <input name="cupom" id="cupom" maxlength="20" value="<?= e($cupom) ?>" placeholder="Opcional"></label>
        </div>
        <div class="acoes"><button class="botao secundario" type="button" id="aplicar-cupom">Aplicar cupom</button></div>
        <p id="cupom-mensagem" role="status"></p>
        <p>Valor da aula <?= reais($h['preco']) ?> · Desconto <span id="desconto"><?= reais(0) ?></span></p>
        <p><strong>Total <span id="total"><?= reais($h['preco']) ?></span></strong></p>
        <div class="acoes"><button class="botao">Enviar solicitação</button><a href="../sprint2/index.php">Voltar à busca</a></div>
    </form>
    <script src="sprint3.js"></script>
    <?php endif; ?>
</section>
<?php require '../sprint1/footer.php'; ?>
