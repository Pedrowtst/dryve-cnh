<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'use_strict_mode' => true]);
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function csrf_token() {
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

function validar_csrf() {
    $token = $_POST["csrf_token"] ?? "";
    if (empty($_SESSION["csrf_token"]) || !is_string($token) || !hash_equals($_SESSION["csrf_token"], $token)) {
        http_response_code(403);
        exit("Solicitação inválida. Atualize a página e tente novamente.");
    }
}

function exigir_login() {
    if (empty($_SESSION["usuario_id"])) {
        header("Location: login.php");
        exit;
    }
}

function exigir_perfil($perfil) {
    exigir_login();
    if (($_SESSION["perfil"] ?? "") !== $perfil) {
        header("Location: painel.php");
        exit;
    }
}

?>
