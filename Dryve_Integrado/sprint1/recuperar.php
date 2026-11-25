<?php
require_once '../app.php';
require_once '../email.php';
$email = $erro = '';
$disponivel = filter_var($email_remetente, FILTER_VALIDATE_EMAIL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $email = campo('email');
    if (!$disponivel) {
        $erro = 'O envio de e-mail ainda não foi configurado neste servidor.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) {
        $erro = 'Informe um e-mail válido.';
    } else {
        $conta = linha('SELECT id FROM usuarios WHERE email = ?', [$email]);
        if ($conta) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            consulta('UPDATE usuarios SET token_recuperacao = ?, token_expira = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id = ?',
                [$hash, $conta['id']]);
            $link = $url_base . '/sprint1/redefinir.php?token=' . $token;
            if (!enviar_email($email, 'Recuperacao de senha Dryve', "Use este link para criar uma nova senha. Ele vale por 30 minutos.\n\n" . $link)) {
                consulta('UPDATE usuarios SET token_recuperacao = NULL, token_expira = NULL WHERE id = ? AND token_recuperacao = ?',
                    [$conta['id'], $hash]);
            }
        }
        redirecionar('recuperar.php', 'Se o e-mail estiver cadastrado, você receberá um link válido por 30 minutos.');
    }
}
$titulo = 'Recuperar senha';
require 'header.php';
?>
<section class="form-layout login-layout">
    <div class="form-intro">
        <h1>Recupere sua senha</h1>
        <p>Informe o e-mail da sua conta.</p>
    </div>
    <div class="form-card">
        <?php avisos_pagina($erro); ?>
        <?php if ($disponivel): ?>
        <form method="post">
            <?php campo_csrf(); ?>
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" maxlength="120" required>
            <button class="button full" type="submit">Enviar link</button>
        </form>
        <?php elseif (!$erro): ?>
            <p role="status">O envio de e-mail ainda não foi configurado neste servidor.</p>
        <?php endif; ?>
        <p class="form-bottom"><a href="login.php">Voltar ao login</a></p>
    </div>
</section>
<?php require 'footer.php'; ?>
