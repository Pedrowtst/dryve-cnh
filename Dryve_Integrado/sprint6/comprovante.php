<?php
require_once '../app.php';
exigir_conta('aluno');
$id = usuario_id();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $regra = linha("SELECT (SELECT COUNT(*) FROM modulos) AS total,
        (SELECT COUNT(*) FROM progresso WHERE aluno_id = ?) AS completos,
        (SELECT COUNT(*) FROM s4_aulas WHERE aluno_id = ? AND status = 'concluida') AS praticas", [$id, $id]);
    if (!$regra['total'] || (int) $regra['completos'] !== (int) $regra['total'] || !$regra['praticas']) {
        redirecionar('index.php', 'O comprovante fica disponível após a conclusão dos módulos e de uma aula prática.');
    }
    consulta('INSERT INTO comprovantes (aluno_id, codigo) VALUES (?, ?) ON DUPLICATE KEY UPDATE aluno_id = aluno_id',
        [$id, strtoupper(bin2hex(random_bytes(8)))]);
    header('Location: comprovante.php', true, 303);
    exit;
}
$comprovante = linha("SELECT c.*, CONCAT(u.nome, ' ', u.sobrenome) AS nome
    FROM comprovantes c JOIN usuarios u ON u.id = c.aluno_id WHERE c.aluno_id = ?", [$id]);
if (!$comprovante) {
    http_response_code(404);
    exit('Seu comprovante ainda não foi emitido.');
}
header('Cache-Control: no-store');
$titulo = 'Comprovante digital';
$modulo = true;
require '../sprint1/header.php';
?>
<section class="modulo">
    <article class="bloco">
        <p><strong>Dryve</strong></p>
        <h1>Comprovante de conclusão</h1>
        <p><strong><?= e($comprovante['nome']) ?></strong> concluiu os módulos de estudo e pelo menos uma aula prática na plataforma Dryve.</p>
        <p>Emissão em <?= dia_hora($comprovante['emitido_em']) ?></p>
        <p>Código <strong><?= e($comprovante['codigo']) ?></strong></p>
        <p>Comprovante acadêmico do Dryve, sem validade para o processo oficial de habilitação.</p>
    </article>
    <div class="acoes nao-imprimir">
        <button class="botao" type="button" onclick="window.print()">Imprimir ou salvar em PDF</button>
        <a href="index.php">Voltar ao progresso</a>
    </div>
</section>
<?php require '../sprint1/footer.php'; ?>
