<?php

require_once "config.php";
require_once "functions.php";
exigir_login();

$titulo = "Meu painel";
require "header.php";
$perfilNome = ucfirst($_SESSION["perfil"]);

?>

<section class="dashboard-welcome">
    <span class="eyebrow">PAINEL <?= e(strtoupper($perfilNome)) ?></span>
    <h1>Olá, <?= e($_SESSION["nome"]) ?>!</h1>
    <p>Você está conectado à sua conta Dryve.</p>
</section>
<section class="dashboard-grid">
    <article class="dashboard-card">
        <h3>Seus dados</h3>
        <p>Consulte e atualize as informações da sua conta.</p>
        <a href="perfil.php">Acessar perfil</a>
    </article>
    <article class="dashboard-card">
        <h3>Agendamentos</h3>
        <p>Veja as solicitações e os horários das suas aulas práticas.</p>
        <a href="../sprint3/index.php">Abrir agendamentos</a>
    </article>
    <?php if ($_SESSION['perfil'] === 'aluno'): ?>
    <article class="dashboard-card">
        <h3>Meu progresso</h3>
        <p>Acompanhe os módulos, o histórico de aulas e os pagamentos.</p>
        <a href="../sprint6/index.php">Ver meu progresso</a>
    </article>
    <?php elseif ($_SESSION['perfil'] === 'instrutor'): ?>
    <article class="dashboard-card">
        <h3>Minha grade</h3>
        <p>Publique horários, pontos de encontro e destinos de aula.</p>
        <a href="../sprint2/grade.php">Gerenciar grade</a>
    </article>
    <article class="dashboard-card">
        <h3>Pós-aula</h3>
        <p>Consulte o histórico e registre o desempenho dos alunos.</p>
        <a href="../sprint6/index.php">Ver histórico de aulas</a>
    </article>
    <?php endif; ?>
    <?php if ($_SESSION["perfil"] === "administrador"): ?>
    <article class="dashboard-card">
        <h3>Usuários cadastrados</h3>
        <p>Consulte os usuários cadastrados na plataforma.</p>
        <a href="usuarios.php">Ver usuários</a>
    </article>
    <article class="dashboard-card">
        <h3>Transmissões</h3>
        <p>Publique aulas ao vivo e atualize o link da gravação.</p>
        <a href="../sprint5/transmissoes.php">Gerenciar transmissões</a>
    </article>

    <?php endif; ?>

</section>

<?php require "footer.php"; ?>
