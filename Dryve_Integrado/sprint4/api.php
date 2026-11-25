<?php
require_once "lib.php";
define('DRYVE_API', true);
try {
    require "config.php";
} catch (Throwable $e) {
    s4_erro("Não foi possível acessar o banco de dados. Verifique config.php e o MySQL.", 503);
}

try {
    $usuario = s4_usuario($conn);
    $userId = (int)$usuario["id"];
    $acao = $_GET["acao"] ?? $_POST["acao"] ?? "";
    if ($_SERVER["REQUEST_METHOD"] === "POST") s4_verificar_token();

    if ($acao === "inicio" && $_SERVER["REQUEST_METHOD"] === "GET") {
        $stmt = $conn->prepare("SELECT a.id, a.inicio, a.status, a.ponto_nome, a.destino_nome, CONCAT(i.nome, ' ', i.sobrenome) AS instrutor FROM s4_aulas a JOIN usuarios i ON i.id = a.instrutor_id WHERE (a.aluno_id = ? OR a.instrutor_id = ?) ORDER BY a.status IN ('concluida','cancelada'), a.inicio ASC LIMIT 80");
        $stmt->bind_param("ii", $userId, $userId);
        $stmt->execute();
        $aulas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        s4_json(["usuario" => $usuario, "csrf" => s4_token(), "aulas" => $aulas]);
    }

    $aulaId = (int)($_GET["aula_id"] ?? $_POST["aula_id"] ?? 0);
    if ($aulaId <= 0) s4_erro("Selecione uma aula.");
    $aula = s4_aula($conn, $aulaId, $userId);

    if ($acao === "estado" && $_SERVER["REQUEST_METHOD"] === "GET") {
        s4_lembrete($conn, $aula);
        $stmt = $conn->prepare("SELECT m.id, m.remetente_id, m.mensagem, DATE_FORMAT(m.criado_em, '%d/%m/%Y %H:%i') AS horario, u.nome AS remetente FROM s4_mensagens m JOIN usuarios u ON u.id = m.remetente_id WHERE m.aula_id = ? ORDER BY m.id DESC LIMIT 80");
        $stmt->bind_param("i", $aulaId);
        $stmt->execute();
        $mensagens = array_reverse($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        $stmt->close();
        $stmt = $conn->prepare("SELECT id, tipo, texto, DATE_FORMAT(criada_em, '%d/%m/%Y %H:%i') AS horario FROM s4_notificacoes WHERE aula_id = ? AND usuario_id = ? ORDER BY id DESC LIMIT 20");
        $stmt->bind_param("ii", $aulaId, $userId);
        $stmt->execute();
        $avisos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $stmt = $conn->prepare("SELECT g.status, t.id AS pagamento FROM agendamentos g
            LEFT JOIN transacoes t ON t.agendamento_id = g.id AND t.tipo = 'pagamento' WHERE g.aula_id = ?");
        $stmt->execute([$aulaId]);
        $vinculo = $stmt->get_result()->fetch_assoc();
        $aula['pode_iniciar'] = !$vinculo || (in_array($vinculo['status'], ['aceito', 'reagendamento'], true) && (bool) $vinculo['pagamento']);
        s4_json(["aula" => $aula, "mensagens" => $mensagens, "avisos" => $avisos, "usuario_id" => $userId, "instrutor" => $userId === (int)$aula["instrutor_id"]]);
    }

    if ($_SERVER["REQUEST_METHOD"] !== "POST") s4_erro("Ação inválida.", 405);
    if (in_array($aula["status"], ["concluida", "cancelada"], true)) s4_erro("Esta aula foi encerrada. Não é possível alterá-la.", 409);

    if ($acao === "enviar") {
        $texto = is_string($_POST['mensagem'] ?? null) ? trim($_POST['mensagem']) : '';
        $tamanho = preg_match_all('/./us', $texto);
        if (!$tamanho || $tamanho > 500) s4_erro("Digite uma mensagem de até 500 caracteres.");
        $stmt = $conn->prepare("INSERT INTO s4_mensagens (aula_id, remetente_id, mensagem)
            SELECT id, ?, ? FROM s4_aulas WHERE id = ? AND status IN ('agendada', 'em_andamento')");
        $stmt->execute([$userId, $texto, $aulaId]);
        if (!$stmt->affected_rows) s4_erro("O chat desta aula foi encerrado.", 409);
        $destinatario = $userId === (int)$aula["aluno_id"] ? (int)$aula["instrutor_id"] : (int)$aula["aluno_id"];
        s4_avisar($conn, $aulaId, $destinatario, "mensagem", "Você recebeu uma nova mensagem na aula Dryve.");
        s4_json(["ok" => true]);
    }

    if (!($userId === (int)$aula["instrutor_id"] && $usuario["perfil"] === "instrutor")) {
        s4_erro("Somente o instrutor desta aula pode realizar essa ação.", 403);
    }

    if ($acao === "localizacao") {
        $comando = $_POST["comando"] ?? "";
        if ($comando === "parar") {
            $stmt = $conn->prepare("UPDATE s4_aulas SET compartilhando = 0, ultima_lat = NULL, ultima_lng = NULL, ultima_localizacao_em = NULL WHERE id = ?");
            $stmt->bind_param("i", $aulaId);
            $stmt->execute();
            $stmt->close();
            s4_json(["ok" => true]);
        }
        if (!in_array($comando, ["iniciar", "atualizar"], true)) s4_erro("Comando de localização inválido.");
        if ($comando === "atualizar" && (int)$aula["compartilhando"] !== 1) s4_erro("O compartilhamento não está ativo.", 409);
        $lat = s4_coordenada($_POST["lat"] ?? "", -90, 90);
        $lng = s4_coordenada($_POST["lng"] ?? "", -180, 180);
        $stmt = $conn->prepare("UPDATE s4_aulas SET compartilhando = 1, ultima_lat = ?, ultima_lng = ?, ultima_localizacao_em = NOW()
            WHERE id = ? AND inicio = ? AND status IN ('agendada','em_andamento') AND (? = 'iniciar' OR compartilhando = 1)");
        $stmt->execute([$lat, $lng, $aulaId, $aula['inicio'], $comando]);
        if ($comando === "iniciar" && $stmt->affected_rows) {
            s4_avisar($conn, $aulaId, (int)$aula["aluno_id"], "localizacao", "O instrutor compartilhou a localização.");
        }
        s4_json(["ok" => true]);
    }

    if ($acao === "atraso") {
        $minutos = filter_var($_POST["minutos"] ?? "", FILTER_VALIDATE_INT);
        if ($minutos === false || $minutos < 0 || $minutos > 120) s4_erro("Informe um atraso válido.");
        $stmt = $conn->prepare("UPDATE s4_aulas SET atraso_minutos = ? WHERE id = ? AND inicio = ? AND status IN ('agendada', 'em_andamento')");
        $stmt->execute([$minutos, $aulaId, $aula['inicio']]);
        if ($stmt->affected_rows) {
            $texto = $minutos > 0 ? "O instrutor informou um atraso estimado de $minutos minutos." : "O instrutor retirou o aviso de atraso da aula.";
            s4_avisar($conn, $aulaId, (int)$aula["aluno_id"], "atraso", $texto);
        }
        s4_json(["ok" => true]);
    }

    if ($acao === "situacao") {
        $novo = $_POST["novo"] ?? "";
        if ($novo === "em_andamento" && $aula["status"] === "agendada") {
            $stmt = $conn->prepare("UPDATE s4_aulas a LEFT JOIN agendamentos g ON g.aula_id = a.id
                LEFT JOIN transacoes t ON t.agendamento_id = g.id AND t.tipo = 'pagamento'
                SET a.status = 'em_andamento', g.status = 'aceito', g.proposta_id = NULL
                WHERE a.id = ? AND a.status = 'agendada'
                AND (g.id IS NULL OR (g.status IN ('aceito', 'reagendamento') AND t.id IS NOT NULL))");
        } elseif ($novo === "concluida" && $aula["status"] === "em_andamento") {
            $stmt = $conn->prepare("UPDATE s4_aulas SET status = 'concluida', compartilhando = 0, ultima_lat = NULL, ultima_lng = NULL, ultima_localizacao_em = NULL WHERE id = ? AND status = 'em_andamento'");
        } else {
            s4_erro("Essa mudança de situação não é permitida.", 409);
        }
        $stmt->bind_param("i", $aulaId);
        $stmt->execute();
        if (!$stmt->affected_rows) s4_erro("Confira se o pagamento e o horário da aula estão confirmados.", 409);
        $stmt->close();
        $texto = $novo === "em_andamento" ? "Seu instrutor marcou a aula como iniciada." : "Seu instrutor concluiu a aula.";
        s4_avisar($conn, $aulaId, (int)$aula["aluno_id"], "situacao", $texto);
        s4_json(["ok" => true]);
    }

    s4_erro("Ação não reconhecida.", 404);
} catch (mysqli_sql_exception $e) {
    s4_erro("Não foi possível acessar os dados de acompanhamento. Confira a importação do database.sql.", 503);
} catch (Throwable $e) {
    s4_erro("Não foi possível concluir a operação.", 500);
}
