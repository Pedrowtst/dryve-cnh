<?php

function enviar_email($destino, $assunto, $mensagem) {
    global $email_remetente;
    if (!filter_var($email_remetente, FILTER_VALIDATE_EMAIL) || !filter_var($destino, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $headers = ['From' => $email_remetente, 'Content-Type' => 'text/plain; charset=UTF-8'];
    return @mail($destino, $assunto, $mensagem, $headers);
}
