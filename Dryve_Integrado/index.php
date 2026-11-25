<?php
require_once __DIR__ . '/sprint1/functions.php';
$logado = !empty($_SESSION["usuario_id"]);
$nome = htmlspecialchars($_SESSION["nome"] ?? "", ENT_QUOTES, "UTF-8");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Dryve: instrutores, agendamentos, acompanhamento e estudos em um único site.">
<title>Dryve | Seu espaço para aprender a dirigir</title>
<link rel="stylesheet" href="assets/site.css">
<link rel="stylesheet" href="assets/acessibilidade.css">
<script src="assets/site.js" defer></script><script src="assets/acessibilidade.js" defer></script>
</head>
<body class="dryve-home">
<a class="dryve-skip" href="#conteudo">Pular para o conteúdo principal</a>
<header class="dryve-site-nav"><div class="dryve-site-nav-inner"><a class="dryve-brand" href="index.php" aria-label="Dryve - voltar ao início">dryve</a><nav class="dryve-site-links" aria-label="Menu principal"><a class="dryve-site-link" href="index.php" aria-current="page">Início</a><a class="dryve-site-link" href="sprint2/index.php">Instrutores</a><a class="dryve-site-link" href="sprint3/index.php">Agendamentos</a><a class="dryve-site-link" href="sprint4/index.html">Acompanhamento</a><a class="dryve-site-link" href="sprint5/index.php">Estudos</a><a class="dryve-site-link" href="sprint6/index.php">Progresso</a><a class="dryve-site-link dryve-account-link" id="dryve-account-link" href="sprint1/login.php">Entrar</a><a class="dryve-site-link dryve-signout" id="dryve-signout" href="sprint1/logout.php" hidden>Sair</a></nav></div>
<div class="accessibility-widget" aria-label="Ferramentas de acessibilidade">
  <button type="button" class="accessibility-trigger" id="accessibility-trigger" aria-controls="accessibility-panel" aria-expanded="false" aria-label="Abrir opções de acessibilidade">
    <span aria-hidden="true">Aa</span>
    <span>Acessibilidade</span>
  </button>
  <section class="accessibility-panel" id="accessibility-panel" aria-labelledby="accessibility-heading" hidden tabindex="-1">
    <div class="accessibility-heading-row">
      <h2 id="accessibility-heading">Acessibilidade</h2>
      <button type="button" class="accessibility-close" id="accessibility-close" aria-label="Fechar opções de acessibilidade">×</button>
    </div>
    <p class="accessibility-label" id="accessibility-size-label">Tamanho do texto</p>
    <div class="accessibility-controls" role="group" aria-labelledby="accessibility-size-label">
      <button type="button" id="accessibility-decrease" aria-label="Diminuir tamanho do texto">A−</button>
      <output id="accessibility-size" class="accessibility-size">100%</output>
      <button type="button" id="accessibility-increase" aria-label="Aumentar tamanho do texto">A+</button>
    </div>
    <button type="button" class="accessibility-option" id="accessibility-contrast" aria-pressed="false">Alto contraste <span aria-hidden="true">○</span></button>
    <button type="button" class="accessibility-option" id="accessibility-spacing" aria-pressed="false">Mais espaço entre linhas <span aria-hidden="true">○</span></button>
    <button type="button" class="accessibility-option" id="accessibility-links" aria-pressed="false">Destacar links <span aria-hidden="true">○</span></button>
    <button type="button" class="accessibility-reset" id="accessibility-reset">Restaurar padrão</button>
    <p class="accessibility-status visually-hidden" id="accessibility-status" role="status" aria-live="polite"></p>

  </section>
</div>
</header>
<main class="dryve-home-main" id="conteudo">
<section class="dryve-home-hero">
  <div class="dryve-home-hero-copy">
    <h1>Aulas e estudos para a CNH</h1>
    <p>Encontre um instrutor, marque suas aulas e pratique para a prova teórica.</p>
    <div class="dryve-home-actions">
      <a class="dryve-home-cta" href="sprint2/index.php">Encontrar instrutor</a>
      <a class="dryve-home-secondary" href="sprint3/index.php">Meus agendamentos</a>
    </div>
    <?php if ($logado): ?>
      <div class="dryve-home-logged">Olá, <?= $nome ?>! <a href="sprint1/painel.php">Abrir meu painel</a></div>
    <?php else: ?>
      <div class="dryve-home-logged">Ainda não tem conta? <a href="sprint1/cadastro.php">Criar conta</a>.</div>
    <?php endif; ?>
  </div>
  <div class="dryve-home-hero-art" aria-hidden="true">
    <div class="dryve-home-art-circle"><span>D</span></div>
  </div>
</section>
<section class="dryve-home-intro"><div><h2>O que você pode fazer</h2></div></section>
<section class="dryve-home-grid" aria-label="Recursos da plataforma">
<a class="dryve-home-card" href="sprint2/index.php"><h3>Encontrar instrutores</h3><p>Pesquise por região, categoria, avaliações e escolha quem vai te acompanhar.</p><span class="dryve-home-go">Explorar instrutores <span aria-hidden="true">→</span></span></a>
<a class="dryve-home-card" href="sprint3/index.php"><h3>Agendar minhas aulas</h3><p>Escolha data e horário, consulte valores e acompanhe solicitações.</p><span class="dryve-home-go">Fazer agendamento <span aria-hidden="true">→</span></span></a>
<a class="dryve-home-card" href="sprint4/index.html"><h3>Acompanhar a aula</h3><p>Veja o mapa, converse pelo chat e receba alertas e lembretes.</p><span class="dryve-home-go">Abrir acompanhamento <span aria-hidden="true">→</span></span></a>
<a class="dryve-home-card" href="sprint5/index.php"><h3>Estudar e praticar</h3><p>Acesse simulados, materiais didáticos e a comunidade de dúvidas.</p><span class="dryve-home-go">Começar a estudar <span aria-hidden="true">→</span></span></a>
<a class="dryve-home-card" href="sprint6/index.php"><h3>Ver meu progresso</h3><p>Confira os módulos concluídos, o pós-aula e o histórico de pagamentos.</p><span class="dryve-home-go">Abrir progresso <span aria-hidden="true">→</span></span></a>
<a class="dryve-home-card dryve-home-card-account" href="<?= $logado ? "sprint1/painel.php" : "sprint1/login.php" ?>"><h3>Minha conta</h3><p>Consulte e atualize seus dados.</p><span class="dryve-home-go">Acessar minha conta <span aria-hidden="true">→</span></span></a>
</section>

</main>
<footer class="dryve-site-footer">Dryve</footer>
</body>
</html>
