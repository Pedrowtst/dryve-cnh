<?php
require_once __DIR__ . '/../app.php';

const CUPONS = ['DRYVE10' => 10, 'PRIMEIRA5' => 5];

function horario_livre($id) {
    $h = linha("SELECT h.*, i.categoria, i.preco, CONCAT(u.nome, ' ', u.sobrenome) AS nome
        FROM horarios h JOIN instrutores i ON i.usuario_id = h.instrutor_id
        JOIN usuarios u ON u.id = h.instrutor_id AND u.perfil = 'instrutor'
        WHERE h.id = ? AND h.inicio > NOW() AND h.disponivel = 1 FOR UPDATE", [$id]);
    if (!$h || linha('SELECT id FROM agendamentos WHERE horario_id = ? AND reserva = 1 FOR UPDATE', [$id])) {
        throw new DomainException('Este horário não está mais disponível. Escolha outro.');
    }
    return $h;
}

function gravar_aula($a, $h) {
    $dados = [$h['inicio'], $h['ponto_nome'], $h['ponto_lat'], $h['ponto_lng'],
        $h['destino_nome'], $h['destino_lat'], $h['destino_lng']];
    if ($a['aula_id']) {
        consulta("UPDATE s4_aulas SET inicio = ?, ponto_nome = ?, ponto_lat = ?, ponto_lng = ?,
            destino_nome = ?, destino_lat = ?, destino_lng = ?, status = 'agendada', atraso_minutos = 0,
            compartilhando = 0, ultima_lat = NULL, ultima_lng = NULL, ultima_localizacao_em = NULL WHERE id = ?",
            [...$dados, $a['aula_id']]);
    } else {
        global $conn;
        consulta('INSERT INTO s4_aulas (aluno_id, instrutor_id, inicio, ponto_nome, ponto_lat, ponto_lng,
            destino_nome, destino_lat, destino_lng) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$a['aluno_id'], $h['instrutor_id'], ...$dados]);
        consulta('UPDATE agendamentos SET aula_id = ? WHERE id = ?', [$conn->insert_id, $a['id']]);
    }
}

function verificar_horario_aluno($aluno, $inicio, $exceto = 0) {
    consulta('SELECT id FROM usuarios WHERE id = ? FOR UPDATE', [$aluno]);
    if (linha("SELECT a.id FROM agendamentos a JOIN horarios h ON h.id = a.horario_id
        WHERE a.aluno_id = ? AND h.inicio = ? AND a.reserva = 1 AND a.id <> ? FOR UPDATE", [$aluno, $inicio, $exceto])) {
        throw new DomainException('O aluno já tem uma aula reservada nesse horário.');
    }
}

function total_agendamento($a) {
    return round($a['valor'] - $a['desconto'], 2);
}
