<?php

require_once __DIR__ . "/functions.php";
$titulo = $titulo ?? "Dryve";

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo) ?> | Dryve</title>
<link rel="stylesheet" href="../sprint1/assets/style.css"><link rel="stylesheet" href="../assets/site.css">
<?php if (!empty($modulo)): ?><link rel="stylesheet" href="../assets/modulos.css"><?php endif; ?>
<link rel="stylesheet" href="../assets/acessibilidade.css">
<script src="../assets/site.js" defer></script><script src="../assets/acessibilidade.js" defer></script>
</head>
<body>
<a class="skip-link" href="#conteudo-principal">Pular para o conteúdo</a>
<header class="dryve-site-nav"><div class="dryve-site-nav-inner"><a class="dryve-brand" href="../index.php" aria-label="Dryve - voltar ao início">dryve</a><nav class="dryve-site-links" aria-label="Menu principal"><a class="dryve-site-link" href="../index.php">Início</a><a class="dryve-site-link" href="../sprint2/index.php">Instrutores</a><a class="dryve-site-link" href="../sprint3/index.php">Agendamentos</a><a class="dryve-site-link" href="../sprint4/index.html">Acompanhamento</a><a class="dryve-site-link" href="../sprint5/index.php">Estudos</a><a class="dryve-site-link" href="../sprint6/index.php">Progresso</a><a class="dryve-site-link dryve-account-link" id="dryve-account-link" href="../sprint1/login.php">Entrar</a><a class="dryve-site-link dryve-signout" id="dryve-signout" href="../sprint1/logout.php" hidden>Sair</a></nav></div>
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
<main class="container" id="conteudo-principal" tabindex="-1">
