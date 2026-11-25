<?php
require_once "config.php";
require_once "functions.php";
exigir_perfil("administrador");

$resultado = $conn->query("SELECT nome, sobrenome, usuario, email, perfil, criado_em FROM usuarios ORDER BY criado_em DESC");
$titulo = "Gerenciar usuários";
require "header.php";
?>
<section class="page-heading"><span class="eyebrow">ADMINISTRAÇÃO</span><h1>Usuários cadastrados</h1><p>Visualização das contas registradas na plataforma.</p></section>
<div class="table-wrap"><table><thead><tr><th>Nome</th><th>Usuário</th><th>E-mail</th><th>Perfil</th><th>Cadastro</th></tr></thead><tbody>
<?php while ($row = $resultado->fetch_assoc()): ?>
<tr><td><?= e($row["nome"] . " " . $row["sobrenome"]) ?></td><td><?= e($row["usuario"]) ?></td><td><?= e($row["email"]) ?></td><td><?= e(ucfirst($row["perfil"])) ?></td><td><?= e($row["criado_em"]) ?></td></tr>
<?php endwhile; ?>
</tbody></table></div>
<?php require "footer.php"; ?>
