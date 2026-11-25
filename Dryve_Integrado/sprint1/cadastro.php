<?php
require_once '../app.php';

$login = $nome = $sobrenome = $email = $perfil = $erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $login = campo('login');
    $nome = campo('nome');
    $sobrenome = campo('sobrenome');
    $email = campo('email');
    $perfil = campo('perfil');
    $senha = $_POST['senha'] ?? '';
    try {
        if (!preg_match('/^[\p{L}\p{N}_.-]{1,20}$/u', $login)) {
            throw new DomainException('No usuário, use até 20 letras ou números. Ponto, traço e sublinhado também são aceitos.');
        }
        texto('nome', 50);
        texto('sobrenome', 50);
        if (strlen($email) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new DomainException('Informe um e-mail válido.');
        if (!is_string($senha) || strlen($senha) < 8 || strlen($senha) > 72 || str_contains($senha, "\0")) {
            throw new DomainException('Use uma senha com pelo menos 8 caracteres, sem ultrapassar 72 bytes.');
        }
        if (!in_array($perfil, ['aluno', 'instrutor'], true)) throw new DomainException('Escolha o perfil Aluno ou Instrutor.');
        consulta('INSERT INTO usuarios (usuario, nome, sobrenome, email, perfil, senha) VALUES (?, ?, ?, ?, ?, ?)',
            [$login, $nome, $sobrenome, $email, $perfil, password_hash($senha, PASSWORD_DEFAULT)]);
        header('Location: login.php?cadastro=sucesso', true, 303);
        exit;
    } catch (DomainException $excecao) {
        $erro = $excecao->getMessage();
    } catch (mysqli_sql_exception $excecao) {
        $erro = $excecao->getCode() === 1062 ? 'Este usuário ou e-mail já está cadastrado.' : 'Não foi possível criar sua conta. Tente de novo.';
    }
    http_response_code(400);
}
$titulo = 'Criar conta';
require 'header.php';
?>
<section class="form-layout">
    <div class="form-intro">
        <h1>Crie sua conta</h1>
        <p>Escolha Aluno para marcar aulas ou Instrutor para publicar seus horários.</p>
    </div>
    <div class="form-card">
        <?php if ($erro): ?><p class="alert error" role="alert"><?= e($erro) ?></p><?php endif; ?>
        <form method="post">
            <?php campo_csrf(); ?>
            <label for="login">Nome de usuário</label>
            <input id="login" name="login" maxlength="20" value="<?= e($login) ?>" autocomplete="username" required>
            <div class="two-columns">
                <div><label for="nome">Nome</label><input id="nome" name="nome" maxlength="50" value="<?= e($nome) ?>" autocomplete="given-name" required></div>
                <div><label for="sobrenome">Sobrenome</label><input id="sobrenome" name="sobrenome" maxlength="50" value="<?= e($sobrenome) ?>" autocomplete="family-name" required></div>
            </div>
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" maxlength="120" value="<?= e($email) ?>" autocomplete="email" required>
            <label for="perfil">Perfil</label>
            <select id="perfil" name="perfil" required>
                <option value="">Selecione</option>
                <option value="aluno" <?= $perfil === 'aluno' ? 'selected' : '' ?>>Aluno</option>
                <option value="instrutor" <?= $perfil === 'instrutor' ? 'selected' : '' ?>>Instrutor</option>
            </select>
            <label for="senha">Senha (pelo menos 8 caracteres)</label>
            <input id="senha" name="senha" type="password" minlength="8" autocomplete="new-password" required>
            <button class="button full" type="submit">Criar conta</button>
        </form>
        <p class="form-bottom">Já tem conta? <a href="login.php">Entrar</a></p>
    </div>
</section>
<?php require 'footer.php'; ?>
