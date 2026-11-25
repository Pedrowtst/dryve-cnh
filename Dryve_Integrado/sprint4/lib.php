<?php
if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== "cli") {
    require_once __DIR__ . '/../sprint1/functions.php';
}

function s4_json($dados, $status = 200) {
    http_response_code($status);
    header("Content-Type: application/json; charset=utf-8");
    header("Cache-Control: no-store");
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function s4_erro($mensagem, $codigo = 400) {
    s4_json(["erro" => $mensagem], $codigo);
}

function s4_usuario($conn) {
    $id = (int)($_SESSION["usuario_id"] ?? 0);
    if (!$id) s4_erro("Entre na sua conta para acompanhar suas aulas.", 401);
    $stmt = $conn->prepare("SELECT id, perfil FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user) s4_erro("Sua sessão não corresponde a um usuário ativo.", 401);
    return $user;
}

function s4_token() {
    if (empty($_SESSION["s4_csrf"])) $_SESSION["s4_csrf"] = bin2hex(random_bytes(32));
    return $_SESSION["s4_csrf"];
}

function s4_verificar_token() {
    $token = $_POST['csrf'] ?? null;
    if (empty($_SESSION['s4_csrf']) || !is_string($token) || !hash_equals($_SESSION['s4_csrf'], $token)) {
        s4_erro("Sessão expirada. Atualize a página.", 403);
    }
}

function s4_aula($conn, $id, $userId) {
    $stmt = $conn->prepare("SELECT a.*, CONCAT(u1.nome, ' ', u1.sobrenome) AS nome_aluno, CONCAT(u2.nome, ' ', u2.sobrenome) AS nome_instrutor, TIMESTAMPDIFF(SECOND, a.ultima_localizacao_em, NOW()) AS segundos_localizacao FROM s4_aulas a JOIN usuarios u1 ON u1.id = a.aluno_id JOIN usuarios u2 ON u2.id = a.instrutor_id WHERE a.id = ? AND (a.aluno_id = ? OR a.instrutor_id = ?) LIMIT 1");
    $stmt->bind_param("iii", $id, $userId, $userId);
    $stmt->execute();
    $aula = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$aula) s4_erro("Você não tem acesso a esta aula.", 403);
    $aula["localizacao_disponivel"] = (int)$aula["compartilhando"] === 1 && $aula["segundos_localizacao"] !== null && (int)$aula["segundos_localizacao"] >= 0 && (int)$aula["segundos_localizacao"] <= 120 && !in_array($aula["status"], ["concluida", "cancelada"], true);
    if (!$aula["localizacao_disponivel"]) {
        $aula["ultima_lat"] = null;
        $aula["ultima_lng"] = null;
    }
    unset($aula["segundos_localizacao"]);
    return $aula;
}

function s4_avisar($conn, $aulaId, $usuarioId, $tipo, $mensagem, $codigo = null) {
    $stmt = $conn->prepare("INSERT IGNORE INTO s4_notificacoes (aula_id, usuario_id, tipo, texto, codigo) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $aulaId, $usuarioId, $tipo, $mensagem, $codigo);
    $stmt->execute();
    $gravou = $stmt->affected_rows > 0;
    $stmt->close();
    if ($gravou && in_array($tipo, ["lembrete", "atraso"], true)) {
        $stmt = $conn->prepare("SELECT email FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $conta = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($conta) enviar_email($conta["email"], "Aviso da aula Dryve", $mensagem);
    }
}

function s4_lembrete($conn, $aula) {
    if ($aula["status"] !== "agendada") return;
    $segundos = strtotime($aula["inicio"]) - time();
    if ($segundos > 0 && $segundos <= 1800) {
        foreach ([(int)$aula["aluno_id"], (int)$aula["instrutor_id"]] as $id) {
            s4_avisar($conn, (int)$aula["id"], $id, "lembrete", "Sua aula Dryve começa em até 30 minutos. Confira o ponto de encontro.", "lembrar-" . $aula["id"] . "-" . $id . "-" . strtotime($aula["inicio"]));
        }
    }
}

function s4_coordenada($valor, $min, $max) {
    if (!is_numeric($valor)) s4_erro("Coordenadas inválidas.");
    $n = (float)$valor;
    if (!is_finite($n) || $n < $min || $n > $max) s4_erro("Coordenadas fora do intervalo permitido.");
    return $n;
}
