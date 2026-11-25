<?php
require_once '../app.php';
exigir_conta();
$id = (int) ($_GET['id'] ?? 0);
$usuario = usuario_id();
$aula = linha("SELECT a.*, CONCAT(i.nome, ' ', i.sobrenome) AS instrutor, CONCAT(u.nome, ' ', u.sobrenome) AS aluno
    FROM s4_aulas a JOIN usuarios i ON i.id = a.instrutor_id JOIN usuarios u ON u.id = a.aluno_id
    WHERE a.id = ? AND a.status = 'concluida' AND (a.aluno_id = ? OR a.instrutor_id = ?)", [$id, $usuario, $usuario]);
if (!$aula) {
    http_response_code(404);
    exit('Aula concluída não encontrada.');
}
$aluno = (int) $aula['aluno_id'] === $usuario && perfil_atual() === 'aluno';
$instrutor = (int) $aula['instrutor_id'] === $usuario && perfil_atual() === 'instrutor';
$opcoes = ['em_pratica' => 'Precisa praticar', 'evoluindo' => 'Em evolução', 'bom' => 'Bom desempenho'];
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    try {
        if ($aluno) {
            $nota = filter_var(campo('nota'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
            if ($nota === false) throw new DomainException('Selecione uma nota de 1 a 5.');
            $comentario = texto('comentario', 500);
            consulta('INSERT INTO avaliacoes (aula_id, nota, comentario) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE nota = ?, comentario = ?', [$id, $nota, $comentario, $nota, $comentario]);
            redirecionar('aula.php?id=' . $id, 'Avaliação salva.');
        }
        if ($instrutor) {
            $desempenho = campo('desempenho');
            if (!isset($opcoes[$desempenho])) throw new DomainException('Selecione o desempenho do aluno.');
            $texto = texto('observacao', 1000);
            consulta('INSERT INTO observacoes (aula_id, desempenho, texto) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE desempenho = ?, texto = ?', [$id, $desempenho, $texto, $desempenho, $texto]);
            redirecionar('aula.php?id=' . $id, 'Observação salva.');
        }
    } catch (DomainException $excecao) {
        $erro = $excecao->getMessage();
    }
}
$avaliacao = linha('SELECT * FROM avaliacoes WHERE aula_id = ?', [$id]);
$observacao = linha('SELECT * FROM observacoes WHERE aula_id = ?', [$id]);
$titulo = 'Pós-aula';
$modulo = true;
require '../sprint1/header.php';
?>
<section class="modulo">
    <h1>Pós-aula</h1>
    <p><?= e($aula['aluno']) ?> e <?= e($aula['instrutor']) ?> · <?= dia_hora($aula['inicio']) ?></p>
    <p><a href="index.php#aulas">Voltar ao histórico</a></p>
    <?php avisos_pagina($erro); ?>
    <div class="bloco">
        <h2>Avaliação do instrutor</h2>
        <?php if ($aluno): ?>
        <form method="post">
            <?php campo_csrf(); ?>
            <label>Estrelas <select name="nota" required><option value="">Selecione</option>
                <?php for ($n = 1; $n <= 5; $n++): ?><option value="<?= $n ?>" <?= (int) ($avaliacao['nota'] ?? 0) === $n ? 'selected' : '' ?>><?= $n ?> ★</option><?php endfor; ?>
            </select></label>
            <label>Comentário <textarea name="comentario" maxlength="500" required><?= e($avaliacao['comentario'] ?? '') ?></textarea></label>
            <p>A nota e o comentário aparecem no perfil público do instrutor.</p>
            <div class="acoes"><button class="botao">Salvar avaliação</button></div>
        </form>
        <?php elseif ($avaliacao): ?>
            <p><?= (int) $avaliacao['nota'] ?>/5 estrelas · <?= e($avaliacao['comentario']) ?></p>
        <?php else: ?><p>O aluno ainda não avaliou a aula.</p><?php endif; ?>
    </div>
    <div class="bloco">
        <h2>Observação pedagógica</h2>
        <?php if ($instrutor): ?>
        <form method="post">
            <?php campo_csrf(); ?>
            <label>Desempenho <select name="desempenho" required><option value="">Selecione</option>
                <?php foreach ($opcoes as $valor => $rotulo): ?><option value="<?= $valor ?>" <?= ($observacao['desempenho'] ?? '') === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option><?php endforeach; ?>
            </select></label>
            <label>Observação <textarea name="observacao" maxlength="1000" required><?= e($observacao['texto'] ?? '') ?></textarea></label>
            <div class="acoes"><button class="botao">Salvar observação</button></div>
        </form>
        <?php elseif ($observacao): ?>
            <p><strong><?= e($opcoes[$observacao['desempenho']]) ?></strong></p><p><?= nl2br(e($observacao['texto'])) ?></p>
        <?php else: ?><p>O instrutor ainda não registrou observações.</p><?php endif; ?>
    </div>
</section>
<?php require '../sprint1/footer.php'; ?>
