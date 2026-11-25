<?php
if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("Este arquivo deve ser executado somente pelo terminal.");
}
require_once "config.php";
require_once "lib.php";
$resultado = $conn->query("SELECT * FROM s4_aulas WHERE status = 'agendada' AND inicio BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 MINUTE)");
$quantidade = 0;
while ($aula = $resultado->fetch_assoc()) {
    s4_lembrete($conn, $aula);
    $quantidade++;
}
echo "Aulas verificadas: $quantidade\n";
