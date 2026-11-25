<?php
require_once '../app.php';
$id = (int) ($_GET['id'] ?? 0);
$pessoa = linha("SELECT CONCAT(u.nome, ' ', u.sobrenome) AS nome
    FROM usuarios u JOIN instrutores i ON i.usuario_id = u.id WHERE u.id = ? AND u.perfil = 'instrutor'", [$id]);
if (!$pessoa) {
    http_response_code(404);
    exit('Instrutor não encontrado.');
}
$lista = linhas('SELECT v.comentario, v.nota FROM avaliacoes v
    JOIN s4_aulas a ON a.id = v.aula_id WHERE a.instrutor_id = ? ORDER BY a.inicio DESC', [$id]);
$titulo = 'Avaliações';
$modulo = true;
require '../sprint1/header.php';
?>
<section class="modulo">
    <h1>Avaliações de <?= e($pessoa['nome']) ?></h1>
    <p><a href="index.php">Voltar à busca de instrutores</a></p>
    <?php if (!$lista): ?><p>Ainda sem avaliações.</p><?php endif; ?>
    <?php foreach ($lista as $avaliacao): ?>
        <article class="bloco"><h2><?= (int) $avaliacao['nota'] ?>/5 estrelas</h2>
            <p><?= nl2br(e($avaliacao['comentario'])) ?></p></article>
    <?php endforeach; ?>
</section>
<?php require '../sprint1/footer.php'; ?>
