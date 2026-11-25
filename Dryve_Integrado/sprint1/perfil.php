<?php

require_once '../app.php';
exigir_login();

$erro = "";
$sucesso = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    validar_csrf();
    $nome = campo('nome');
    $sobrenome = campo('sobrenome');
    $email = campo('email');
    $senhaAtual = is_string($_POST['senha_atual'] ?? null) ? $_POST['senha_atual'] : '';
    $novaSenha = is_string($_POST['nova_senha'] ?? null) ? $_POST['nova_senha'] : '';
    $conta = linha('SELECT email, senha FROM usuarios WHERE id = ?', [usuario_id()]);
    if (!$conta) {
        header('Location: logout.php');
        exit;
    }
    $alterarAcesso = $novaSenha !== '' || $email !== $conta['email'];

    if (!$nome || !$sobrenome || preg_match_all('/./us', $nome) > 50 || preg_match_all('/./us', $sobrenome) > 50 ||
        strlen($email) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Preencha nome, sobrenome e um e-mail válido.";
    } elseif ($alterarAcesso && !password_verify($senhaAtual, $conta['senha'])) {
        $erro = 'Confirme a senha atual para alterar o e-mail ou a senha.';
    } elseif ($novaSenha !== '' && (strlen($novaSenha) < 8 || strlen($novaSenha) > 72 || str_contains($novaSenha, "\0"))) {
        $erro = 'Use uma senha com pelo menos 8 caracteres, sem ultrapassar 72 bytes.';
    } else {
        try {
            if ($alterarAcesso) {
                $hash = $novaSenha !== '' ? password_hash($novaSenha, PASSWORD_DEFAULT) : $conta['senha'];
                consulta('UPDATE usuarios SET nome = ?, sobrenome = ?, email = ?, senha = ?,
                    token_recuperacao = NULL, token_expira = NULL WHERE id = ?', [$nome, $sobrenome, $email, $hash, usuario_id()]);
            } else {
                consulta('UPDATE usuarios SET nome = ?, sobrenome = ? WHERE id = ?', [$nome, $sobrenome, usuario_id()]);
            }
            $_SESSION['nome'] = $nome;
            $_SESSION['sobrenome'] = $sobrenome;
            $_SESSION['email'] = $email;
            $sucesso = 'Dados atualizados com sucesso.';
        } catch (mysqli_sql_exception $excecao) {
            $erro = $excecao->getCode() === 1062 ? 'Esse e-mail já está sendo utilizado por outra conta.' : 'Não foi possível atualizar seus dados.';
        }
    }
}
$titulo = "Meu perfil";
require "header.php";

?>

<section class="page-heading"><span class="eyebrow">MINHA CONTA</span><h1>Meu perfil</h1><p>Atualize seus dados de acesso e informações pessoais.</p></section>
<div class="form-card profile-card">
    <?php if ($erro): ?><div class="alert error"><?= e($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?><div class="alert success"><?= e($sucesso) ?></div><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="nome">Nome</label><input id="nome" name="nome" maxlength="50" required value="<?= e($_SESSION["nome"]) ?>">
        <label for="sobrenome">Sobrenome</label><input id="sobrenome" name="sobrenome" maxlength="50" required value="<?= e($_SESSION["sobrenome"]) ?>">
        <label for="email">E-mail</label><input id="email" type="email" name="email" maxlength="120" required value="<?= e($_SESSION["email"]) ?>">
        <label for="usuario_visual">Nome de usuário</label><input id="usuario_visual" value="<?= e($_SESSION["login"]) ?>" disabled>
        <label for="perfil_visual">Perfil</label><input id="perfil_visual" value="<?= e(ucfirst($_SESSION["perfil"])) ?>" disabled>
        <div class="form-divider"></div>
        <h3>Dados de acesso</h3>
        <label for="senha_atual">Senha atual (ao alterar e-mail ou senha)</label><input id="senha_atual" type="password" name="senha_atual" autocomplete="current-password">
        <label for="nova_senha">Nova senha (opcional)</label><input id="nova_senha" type="password" name="nova_senha" minlength="8" autocomplete="new-password">
        <button class="button full" type="submit">Salvar alterações</button>
    </form>
</div>

<?php require "footer.php"; ?>
