<?php
require_once '../app.php';

$login = '';
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $login = campo('login');
    $senha = $_POST['senha'] ?? '';
    $dados = linha('SELECT * FROM usuarios WHERE usuario = ? OR email = ? LIMIT 1', [$login, $login]);

    if ($dados && is_string($senha) && strlen($senha) <= 72 && password_verify($senha, $dados['senha'])) {
        $horario = (int) ($_SESSION['horario_login'] ?? 0);
        session_regenerate_id(true);
        $_SESSION = [
            'usuario_id' => $dados['id'], 'login' => $dados['usuario'], 'nome' => $dados['nome'],
            'sobrenome' => $dados['sobrenome'], 'email' => $dados['email'], 'perfil' => $dados['perfil'],
        ];
        $destino = $horario && $dados['perfil'] === 'aluno' ? '../sprint3/agendar.php?horario=' . $horario : 'painel.php';
        header('Location: ' . $destino, true, 303);
        exit;
    }
    http_response_code(401);
    $erro = 'Usuário, e-mail ou senha incorretos.';
}
$titulo = 'Entrar';
require 'header.php';
?>
<section class="form-layout login-layout">
    <div class="form-intro">
        <h1>Entre no Dryve</h1>
        <p>Acesse sua conta para consultar suas aulas e seus estudos.</p>
    </div>
    <div class="form-card">
        <?php if (($_GET['cadastro'] ?? '') === 'sucesso'): ?><p class="alert success" role="status">Conta criada. Entre com seu usuário ou e-mail.</p><?php endif; ?>
        <?php if ($erro): ?><p class="alert error" role="alert"><?= e($erro) ?></p><?php endif; ?>
        <form method="post">
            <?php campo_csrf(); ?>
            <label for="login">Usuário ou e-mail</label>
            <input id="login" name="login" value="<?= e($login) ?>" autocomplete="username" maxlength="120" required>
            <label for="senha">Senha</label>
            <input id="senha" name="senha" type="password" autocomplete="current-password" required>
            <button class="button full" type="submit">Entrar</button>
        </form>
        <a class="forgot-link" href="recuperar.php">Esqueci minha senha</a>
        <p class="form-bottom">Ainda não tem conta? <a href="cadastro.php">Cadastre-se</a></p>
    </div>
</section>
<?php require 'footer.php'; ?>
