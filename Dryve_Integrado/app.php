<?php
require_once __DIR__ . '/sprint1/functions.php';
require_once __DIR__ . '/sprint1/config.php';

function consulta($sql, $parametros = []) {
    global $conn;
    $stmt = $conn->prepare($sql);
    $stmt->execute($parametros);
    return $stmt;
}

function linhas($sql, $parametros = []) {
    return consulta($sql, $parametros)->get_result()->fetch_all(MYSQLI_ASSOC);
}

function linha($sql, $parametros = []) {
    return consulta($sql, $parametros)->get_result()->fetch_assoc();
}

function usuario_id() {
    return (int) ($_SESSION['usuario_id'] ?? 0);
}

function perfil_atual() {
    return $_SESSION['perfil'] ?? '';
}

function exigir_conta($perfil = null) {
    if (!usuario_id()) {
        header('Location: ../sprint1/login.php');
        exit;
    }
    if ($perfil && perfil_atual() !== $perfil) {
        http_response_code(403);
        exit('Esta opção não está disponível para o seu perfil.');
    }
}

function campo($nome) {
    return is_string($_POST[$nome] ?? null) ? trim($_POST[$nome]) : '';
}

function texto($nome, $limite) {
    $valor = campo($nome);
    $tamanho = preg_match_all('/./us', $valor);
    if (!$tamanho || $tamanho > $limite) {
        throw new DomainException("Preencha o campo com até $limite caracteres.");
    }
    return $valor;
}

function data_hora($valor) {
    $data = DateTime::createFromFormat('!Y-m-d\TH:i', $valor);
    if (!$data || $data->format('Y-m-d\TH:i') !== $valor || $data <= new DateTime()) {
        throw new DomainException('Escolha uma data e um horário no futuro.');
    }
    return $data->format('Y-m-d H:i:s');
}

function campo_csrf() {
    echo '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function redirecionar($pagina, $mensagem) {
    $_SESSION['mensagem'] = $mensagem;
    header('Location: ' . $pagina, true, 303);
    exit;
}

function avisos_pagina($erro = '') {
    if ($erro) {
        echo '<p class="alert error" role="alert">' . e($erro) . '</p>';
    }
    if (isset($_SESSION['mensagem'])) {
        echo '<p class="alert success" role="status">' . e($_SESSION['mensagem']) . '</p>';
        unset($_SESSION['mensagem']);
    }
}

function dia_hora($valor) {
    return date('d/m/Y, H:i', strtotime($valor));
}

function reais($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}
