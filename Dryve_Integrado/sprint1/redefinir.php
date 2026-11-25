<?php
require_once "config.php";
require_once "functions.php";

$token = $_POST["token"] ?? $_GET["token"] ?? "";
$usuario = null;
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
$erro = "";
$sucesso = "";

if (is_string($token) && preg_match('/^[a-f0-9]{64}$/D', $token)) {
    $tokenHash = hash('sha256', $token);
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE token_recuperacao = ? AND token_expira > NOW() LIMIT 1");
    $stmt->bind_param("s", $tokenHash);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        validar_csrf();
        $senha = $_POST["senha"] ?? "";
        $confirmacao = $_POST["confirmacao"] ?? "";
        if (!$usuario) {
            $erro = "Esse link é inválido ou expirou. Solicite uma nova recuperação.";
        } elseif (!is_string($senha) || strlen($senha) < 8 || strlen($senha) > 72 || str_contains($senha, "\0")) {
            $erro = 'Use uma senha com pelo menos 8 caracteres, sem ultrapassar 72 bytes.';
        } elseif ($senha !== $confirmacao) {
            $erro = "As senhas não são iguais.";
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET senha = ?, token_recuperacao = NULL, token_expira = NULL WHERE id = ? AND token_recuperacao = ? AND token_expira > NOW()");
            $stmt->bind_param("sis", $hash, $usuario["id"], $tokenHash);
            $stmt->execute();
            if ($stmt->affected_rows === 1) {
                $sucesso = "Senha alterada. Você já pode entrar na sua conta.";
            } else {
                $erro = "Esse link já foi usado ou expirou.";
                $usuario = null;
            }
            $stmt->close();
        }
    }
} else {
    $usuario = null;
}
$titulo = "Redefinir senha";
require "header.php";
?>
<section class="form-layout login-layout">
    <div class="form-intro"><span class="eyebrow">NOVA SENHA</span><h1>Crie uma nova senha.</h1><p>Escolha uma senha segura para voltar a acessar a Dryve.</p></div>
    <div class="form-card">
        <h2>Redefinir senha</h2>
        <?php if ($erro): ?><div class="alert error"><?= e($erro) ?></div><?php endif; ?>
        <?php if ($sucesso): ?><div class="alert success"><?= e($sucesso) ?></div><a class="button full" href="login.php">Ir para o login</a>
        <?php elseif (!$usuario): ?><div class="alert error">Link inválido ou expirado. Solicite uma nova recuperação.</div><a href="recuperar.php">Solicitar novamente</a>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <label for="senha">Nova senha</label><input id="senha" type="password" name="senha" minlength="8" required>
            <label for="confirmacao">Confirme a nova senha</label><input id="confirmacao" type="password" name="confirmacao" minlength="8" required>
            <button class="button full" type="submit">Salvar nova senha</button>
        </form>
        <?php endif; ?>
    </div>
</section>
<?php require "footer.php"; ?>
