<?php
require_once '../app.php';
exigir_conta('administrador');
$erro = '';

function link_video($campo) {
    $link = texto($campo, 500);
    if (!filter_var($link, FILTER_VALIDATE_URL) || strtolower(parse_url($link, PHP_URL_SCHEME)) !== 'https') {
        throw new DomainException('Informe um link HTTPS válido.');
    }
    return $link;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    try {
        if (campo('acao') === 'gravar') {
            consulta('UPDATE transmissoes SET link_gravacao = ? WHERE id = ?', [link_video('gravacao'), (int) campo('id')]);
            redirecionar('transmissoes.php', 'Link da gravação atualizado.');
        }
        if (campo('acao') === 'publicar') {
            consulta('INSERT INTO transmissoes (titulo, inicio, link_ao_vivo) VALUES (?, ?, ?)',
                [texto('titulo', 100), data_hora(campo('inicio')), link_video('link')]);
            redirecionar('transmissoes.php', 'Transmissão publicada.');
        }
    } catch (DomainException $excecao) {
        $erro = $excecao->getMessage();
    }
}
$transmissoes = linhas('SELECT * FROM transmissoes ORDER BY inicio DESC');
$titulo = 'Gerenciar transmissões';
$modulo = true;
require '../sprint1/header.php';
?>
<section class="modulo">
    <h1>Gerenciar transmissões</h1>
    <p><a href="index.php#transmissoes">Voltar aos estudos</a></p>
    <?php avisos_pagina($erro); ?>
    <form method="post" class="bloco">
        <?php campo_csrf(); ?>
        <h2>Publicar aula ao vivo</h2>
        <div class="campos">
            <label>Título <input name="titulo" maxlength="100" required></label>
            <label>Data e horário <input type="datetime-local" name="inicio" required></label>
            <label>Link da transmissão <input type="url" name="link" maxlength="500" required></label>
        </div>
        <div class="acoes"><button class="botao" name="acao" value="publicar">Publicar transmissão</button></div>
    </form>
    <?php foreach ($transmissoes as $t): ?>
        <form class="bloco" method="post">
            <?php campo_csrf(); ?><input type="hidden" name="id" value="<?= $t['id'] ?>">
            <h2><?= e($t['titulo']) ?></h2>
            <p><?= dia_hora($t['inicio']) ?></p>
            <label>Link da gravação <input type="url" name="gravacao" maxlength="500" value="<?= e($t['link_gravacao'] ?? '') ?>" required></label>
            <div class="acoes"><button class="botao secundario" name="acao" value="gravar">Salvar gravação</button></div>
        </form>
    <?php endforeach; ?>
</section>
<?php require '../sprint1/footer.php'; ?>
