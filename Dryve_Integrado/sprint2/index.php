<?php
require_once '../app.php';
$aluno = perfil_atual() === 'aluno' ? usuario_id() : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_conta('aluno');
    validar_csrf();
    $id = (int) campo('instrutor');
    if (linha('SELECT usuario_id FROM instrutores WHERE usuario_id = ?', [$id])) {
        if (campo('acao') === 'remover') {
            consulta('DELETE FROM favoritos WHERE aluno_id = ? AND instrutor_id = ?', [$aluno, $id]);
        } else {
            consulta('INSERT IGNORE INTO favoritos (aluno_id, instrutor_id) VALUES (?, ?)', [$aluno, $id]);
        }
    }
}

$local = is_string($_GET['local'] ?? null) ? trim($_GET['local']) : '';
$termo = preg_replace('/[\s,]+/u', ' ', $local) ?? '';
$categoria = in_array($_GET['categoria'] ?? '', ['A', 'B'], true) ? $_GET['categoria'] : '';
$nota = filter_var($_GET['nota'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 5]]) ?: 0;
$soFavoritos = isset($_GET['favoritos']);
$lat = filter_var($_GET['lat'] ?? '', FILTER_VALIDATE_FLOAT);
$lng = filter_var($_GET['lng'] ?? '', FILTER_VALIDATE_FLOAT);
$usarGps = $lat !== false && $lng !== false && abs($lat) <= 90 && abs($lng) <= 180;
$instrutores = linhas("
    SELECT i.*, CONCAT(u.nome, ' ', u.sobrenome) AS nome, COALESCE(n.nota, 0) AS nota,
           COALESCE(n.total, 0) AS total, f.aluno_id IS NOT NULL AS favorito
    FROM instrutores i JOIN usuarios u ON u.id = i.usuario_id AND u.perfil = 'instrutor'
    LEFT JOIN (
        SELECT a.instrutor_id, AVG(v.nota) AS nota, COUNT(*) AS total
        FROM avaliacoes v JOIN s4_aulas a ON a.id = v.aula_id GROUP BY a.instrutor_id
    ) n ON n.instrutor_id = i.usuario_id
    LEFT JOIN favoritos f ON f.instrutor_id = i.usuario_id AND f.aluno_id = ?
    WHERE LOCATE(?, CONCAT(i.bairro, ' ', i.cidade)) > 0
      AND (? = '' OR i.categoria = ? OR i.categoria = 'A/B')
      AND COALESCE(n.nota, 0) >= ?
      AND (? = 0 OR f.aluno_id IS NOT NULL)
    ORDER BY nota DESC, nome", [$aluno, trim($termo), $categoria, $categoria, $nota, (int) $soFavoritos]);
$grade = [];
foreach (linhas("SELECT h.* FROM horarios h
    LEFT JOIN agendamentos a ON a.horario_id = h.id AND a.reserva = 1
    WHERE h.disponivel = 1 AND h.inicio > NOW() AND a.id IS NULL ORDER BY h.inicio") as $horario) {
    $grade[$horario['instrutor_id']][] = $horario;
}
if ($usarGps) {
    foreach ($instrutores as &$instrutor) {
        $distancias = [];
        foreach ($grade[$instrutor['usuario_id']] ?? [] as $h) {
            $dLat = deg2rad($h['ponto_lat'] - $lat);
            $dLng = deg2rad($h['ponto_lng'] - $lng);
            $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat)) * cos(deg2rad($h['ponto_lat'])) * sin($dLng / 2) ** 2;
            $distancias[] = 12742 * asin(sqrt(min(1, $a)));
        }
        $instrutor['distancia'] = $distancias ? min($distancias) : null;
    }
    unset($instrutor);
    usort($instrutores, fn($a, $b) => ($a['distancia'] ?? INF) <=> ($b['distancia'] ?? INF));
}
$titulo = 'Instrutores';
$modulo = true;
require '../sprint1/header.php';
?>
<section class="modulo">
    <h1>Encontre um instrutor</h1>
    <p>Busque por cidade ou bairro e confira a grade de aulas práticas.</p>
    <?php if (perfil_atual() === 'instrutor'): ?><a class="botao" href="grade.php">Gerenciar minha grade</a><?php endif; ?>
    <form method="get" class="bloco" id="busca">
        <div class="campos">
            <label>Bairro ou cidade <input name="local" value="<?= e($local) ?>" maxlength="80"></label>
            <label>Categoria <select name="categoria"><option value="">Todas</option>
                <?php foreach (['A', 'B'] as $opcao): ?><option <?= $categoria === $opcao ? 'selected' : '' ?>><?= $opcao ?></option><?php endforeach; ?>
            </select></label>
            <label>Avaliação mínima <select name="nota"><option value="0">Qualquer nota</option>
                <?php for ($i = 1; $i <= 5; $i++): ?><option value="<?= $i ?>" <?= $nota === $i ? 'selected' : '' ?>><?= $i ?> estrelas</option><?php endfor; ?>
            </select></label>
        </div>
        <input type="hidden" name="lat" id="busca-lat" value="<?= $usarGps ? e($lat) : '' ?>">
        <input type="hidden" name="lng" id="busca-lng" value="<?= $usarGps ? e($lng) : '' ?>">
        <div class="acoes">
            <button class="botao">Pesquisar instrutores</button>
            <button class="botao secundario" type="button" id="usar-gps">Usar minha localização</button>
            <?php if ($aluno): ?><button class="botao secundario" name="favoritos" value="1">Ver favoritos</button><?php endif; ?>
            <a class="botao secundario" href="index.php">Limpar filtros</a>
        </div>
        <p id="mensagem-gps" role="status"><?= $usarGps ? 'Ordenado pela distância ao ponto de encontro mais próximo.' : '' ?></p>
    </form>
    <h2><?= $soFavoritos ? 'Meus favoritos' : 'Resultados' ?></h2>
    <?php if (!$instrutores): ?><p>Nenhum instrutor encontrado. Tente outros filtros.</p><?php endif; ?>
    <div class="grade">
    <?php foreach ($instrutores as $i): $horarios = $grade[$i['usuario_id']] ?? []; ?>
        <article class="bloco">
            <h3><?= e($i['nome']) ?></h3>
            <p><?= e($i['bairro'] . ', ' . $i['cidade']) ?></p>
            <p><?= $i['total'] ? e(number_format($i['nota'], 1, ',', '.') . '/5 · ' . $i['total'] . ' avaliação(ões)') : 'Ainda sem avaliações' ?></p>
            <?php if ($i['total']): ?><p><a href="avaliacoes.php?id=<?= (int) $i['usuario_id'] ?>">Ler avaliações</a></p><?php endif; ?>
            <p>Categoria <?= e($i['categoria']) ?> · <?= reais($i['preco']) ?> por aula</p>
            <?php if ($usarGps && $i['distancia'] !== null): ?><p><?= e(number_format($i['distancia'], 1, ',', '.')) ?> km em linha reta</p><?php endif; ?>
            <details><summary><?= count($horarios) ?> horário(s) disponível(is)</summary>
                <?php foreach ($horarios as $h): ?>
                    <div class="linha">
                        <p><?= dia_hora($h['inicio']) ?> · <?= e($h['ponto_nome']) ?> → <?= e($h['destino_nome']) ?></p>
                        <?php if (perfil_atual() !== 'instrutor' && perfil_atual() !== 'administrador'): ?>
                            <a href="../sprint3/agendar.php?horario=<?= (int) $h['id'] ?>">Agendar aula</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </details>
            <?php if ($aluno): ?>
                <form method="post" class="acoes">
                    <?php campo_csrf(); ?><input type="hidden" name="instrutor" value="<?= (int) $i['usuario_id'] ?>">
                    <button class="botao secundario" name="acao" value="<?= $i['favorito'] ? 'remover' : 'favoritar' ?>"><?= $i['favorito'] ? 'Remover favorito' : 'Favoritar' ?></button>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    </div>
</section>
<script src="script.js"></script>
<?php require '../sprint1/footer.php'; ?>
