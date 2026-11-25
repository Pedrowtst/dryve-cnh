<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

date_default_timezone_set('America/Sao_Paulo');
$host = getenv('DRYVE_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('DRYVE_DB_PORT') ?: 3306);
$user = getenv('DRYVE_DB_USER') ?: 'root';
$password = getenv('DRYVE_DB_PASSWORD') ?: '';
$database = "dryve";

$url_base = rtrim(getenv('DRYVE_URL') ?: 'http://localhost/Dryve_Integrado', '/');
$email_remetente = getenv('DRYVE_EMAIL_REMETENTE') ?: '';

try {
    $conn = new mysqli($host, $user, $password, $database, $port);
    $conn->set_charset('utf8mb4');
    $conn->query("SET time_zone = '" . date('P') . "'");
} catch (mysqli_sql_exception $e) {
    if (defined('DRYVE_API')) throw $e;
    http_response_code(503);
    exit('Banco de dados indisponível. Confira o MySQL e o arquivo config.php.');
}
