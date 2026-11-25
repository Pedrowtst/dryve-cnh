<?php
require_once '../app.php';

$id = usuario_id();
$aluno = perfil_atual() === 'aluno';
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_conta();
    validar_csrf();
    try {
        if (campo('acao') === 'concluir' && $aluno) {
            consulta('INSERT IGNORE INTO progresso (aluno_id, modulo_id) SELECT ?, id FROM modulos WHERE id = ?',
                [$id, (int) campo('modulo')]);
            redirecionar('index.php#aulas', 'Módulo marcado como concluído.');
        }
        if (campo('acao') === 'publicar') {
            $texto = texto('mensagem', 800);
            $resposta = (int) campo('resposta_a');
            if ($resposta && !linha('SELECT id FROM publicacoes WHERE id = ? AND resposta_a IS NULL', [$resposta])) {
                throw new DomainException('A publicação não foi encontrada.');
            }
            consulta('INSERT INTO publicacoes (usuario_id, mensagem, resposta_a) VALUES (?, ?, ?)', [$id, $texto, $resposta ?: null]);
            redirecionar('index.php#comunidade', $resposta ? 'Resposta publicada.' : 'Publicação enviada.');
        }
    } catch (DomainException $excecao) {
        $erro = $excecao->getMessage();
    }
}
$modulos = linhas('SELECT m.*, p.concluido_em FROM modulos m
    LEFT JOIN progresso p ON m.id = p.modulo_id AND p.aluno_id = ? ORDER BY m.id', [$id]);
$transmissoes = linhas('SELECT * FROM transmissoes ORDER BY inicio DESC');
$posts = linhas('SELECT p.*, u.nome FROM publicacoes p JOIN usuarios u ON u.id = p.usuario_id
    WHERE p.resposta_a IS NULL ORDER BY p.id DESC LIMIT 30');
$respostas = [];
foreach (linhas('SELECT p.*, u.nome FROM publicacoes p JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.resposta_a IS NOT NULL ORDER BY p.id') as $resposta) {
    $respostas[$resposta['resposta_a']][] = $resposta;
}
$materiais = ['guia-sinalizacao.pdf' => 'Guia de sinalização',
    'manual-direcao-defensiva.pdf' => 'Manual de direção defensiva',
    'resumo-legislacao.pdf' => 'Resumo de legislação'];
$titulo = 'Estudos';
$modulo = true;
require '../sprint1/header.php';
?>
<section class="modulo">
    <h1>Estudos</h1>
    <nav class="acoes atalhos" aria-label="Opções de estudo">
        <a href="#aulas">Aulas</a><a href="simulado.php">Simulado</a><a href="#comunidade">Comunidade</a><a href="#materiais">Materiais</a>
        <?php if ($aluno): ?><a href="../sprint6/index.php">Meu progresso</a><?php endif; ?>
        <?php if (perfil_atual() === 'administrador'): ?><a href="transmissoes.php">Gerenciar transmissões</a><?php endif; ?>
    </nav>
    <?php avisos_pagina($erro); ?>
    <section class="bloco" id="aulas">
        <h2>Módulos gravados</h2>
        <?php foreach ($modulos as $m): ?>
        <div class="linha">
            <h3><?= e($m['titulo']) ?></h3>
            <p><?= e($m['descricao']) ?></p>
            <div class="acoes">
                <a href="<?= e($m['video_url']) ?>" target="_blank" rel="noopener noreferrer">Assistir ao vídeo</a>
                <?php if ($aluno && !$m['concluido_em']): ?>
                    <form method="post"><?php campo_csrf(); ?><input type="hidden" name="modulo" value="<?= $m['id'] ?>">
                        <button class="botao secundario" name="acao" value="concluir">Marcar como concluído</button>
                    </form>
                <?php elseif ($m['concluido_em']): ?><span class="indicador">Concluído</span><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (!$id): ?><p><a href="../sprint1/login.php">Entre</a> para registrar seu progresso.</p><?php endif; ?>
    </section>
    <section class="bloco" id="transmissoes">
        <h2>Aulas ao vivo e gravações</h2>
        <?php if (!$transmissoes): ?><p>Nenhuma transmissão publicada.</p><?php endif; ?>
        <?php foreach ($transmissoes as $t): ?>
        <article class="linha">
            <h3><?= e($t['titulo']) ?></h3><p><?= dia_hora($t['inicio']) ?></p>
            <?php if ($t['link_gravacao']): ?>
                <a href="<?= e($t['link_gravacao']) ?>" target="_blank" rel="noopener noreferrer">Assistir à gravação</a>
            <?php else: ?><a href="<?= e($t['link_ao_vivo']) ?>" target="_blank" rel="noopener noreferrer">Abrir transmissão</a><?php endif; ?>
        </article>
        <?php endforeach; ?>
    </section>
    <section class="bloco">
        <h2>Simulado teórico</h2>
        <p>São 30 questões de múltipla escolha, com correção ao final e referência de 20 acertos.</p>
        <a class="botao" href="simulado.php">Abrir simulado</a>
    </section>
    <section class="bloco" id="comunidade">
        <h2>Comunidade de dúvidas</h2>
        <?php if ($id): ?>
            <form method="post"><?php campo_csrf(); ?>
                <label>Escreva uma dúvida ou contribuição <textarea name="mensagem" maxlength="800" required></textarea></label>
                <div class="acoes"><button class="botao" name="acao" value="publicar">Publicar</button></div>
            </form>
        <?php else: ?><p><a href="../sprint1/login.php">Entre</a> para publicar ou responder.</p><?php endif; ?>
        <?php if (!$posts): ?><p>A comunidade ainda não tem publicações.</p><?php endif; ?>
        <?php foreach ($posts as $post): ?>
            <article class="linha">
                <h3><?= e($post['nome']) ?></h3><small><?= dia_hora($post['criada_em']) ?></small>
                <p><?= nl2br(e($post['mensagem'])) ?></p>
                <div class="respostas">
                    <?php foreach ($respostas[$post['id']] ?? [] as $resposta): ?>
                        <div class="linha">
                            <strong><?= e($resposta['nome']) ?></strong> · <small><?= dia_hora($resposta['criada_em']) ?></small>
                            <p><?= nl2br(e($resposta['mensagem'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($id): ?>
                        <details><summary>Responder</summary><form method="post">
                            <?php campo_csrf(); ?><input type="hidden" name="resposta_a" value="<?= $post['id'] ?>">
                            <label>Resposta <textarea name="mensagem" maxlength="800" required></textarea></label>
                            <div class="acoes"><button class="botao secundario" name="acao" value="publicar">Publicar resposta</button></div>
                        </form></details>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
    <section class="bloco" id="materiais">
        <h2>Materiais para baixar</h2>
        <ul><?php foreach ($materiais as $arquivo => $rotulo): ?><li><a download href="materiais/<?= $arquivo ?>"><?= e($rotulo) ?> (PDF)</a></li><?php endforeach; ?></ul>
    </section>
</section>
<?php require '../sprint1/footer.php'; ?>
