<?php
require_once __DIR__ . '/sprint1/functions.php';
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");
echo json_encode([
    "logado" => !empty($_SESSION["usuario_id"]),
    "nome" => $_SESSION["nome"] ?? "",
    "perfil" => $_SESSION["perfil"] ?? ""
], JSON_UNESCAPED_UNICODE);
