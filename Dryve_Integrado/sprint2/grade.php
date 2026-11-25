<?php
require_once '../app.php';
exigir_conta('instrutor');
$id = usuario_id();
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    try {
        $acao = campo('acao');
        if ($acao === 'perfil') {
            $categoria = campo('categoria');
            $preco = filter_var(campo('preco'), FILTER_VALIDATE_FLOAT);
            if (!in_array($categoria, ['A', 'B', 'A/B'], true) || $preco === false || $preco < 1 || $preco > 9999) {
                throw new DomainException('Informe uma categoria e um valor por aula entre R$ 1,00 e R$ 9.999,00.');
            }
            $bairro = texto('bairro', 80);
            $cidade = texto('cidade', 80);
            consulta('INSERT INTO instrutores (usuario_id, categoria, bairro, cidade, preco) VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE categoria = ?, bairro = ?, cidade = ?, preco = ?',
                [$id, $categoria, $bairro, $cidade, $preco, $categoria, $bairro, $cidade, $preco]);
            redirecionar('grade.php', 'Perfil de instrutor atualizado.');
        }
        if ($acao === 'horario') {
            $inicio = data_hora(campo('inicio'));
            $coordenadas = [];
            foreach (['ponto_lat' => 90, 'ponto_lng' => 180, 'destino_lat' => 90, 'destino_lng' => 180] as $nome => $limite) {
                $valor = filter_var(str_replace(',', '.', campo($nome)), FILTER_VALIDATE_FLOAT);
                if ($valor === false || abs($valor) > $limite) {
                    throw new DomainException('Confira a latitude e a longitude do encontro e do destino.');
                }
                $coordenadas[$nome] = $valor;
            }
            consulta('INSERT INTO horarios
                (instrutor_id, inicio, ponto_nome, ponto_lat, ponto_lng, destino_nome, destino_lat, destino_lng)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [$id, $inicio, texto('ponto_nome', 150),
                $coordenadas['ponto_lat'], $coordenadas['ponto_lng'], texto('destino_nome', 150),
                $coordenadas['destino_lat'], $coordenadas['destino_lng']]);
            redirecionar('grade.php', 'Horário publicado.');
        }
        if ($acao === 'disponibilidade') {
            $horario = (int) campo('horario');
            $conn->begin_transaction();
            $h = linha('SELECT id FROM horarios WHERE id = ? AND instrutor_id = ? AND inicio > NOW() FOR UPDATE', [$horario, $id]);
            if (!$h || linha('SELECT id FROM agendamentos WHERE horario_id = ? AND reserva = 1 FOR UPDATE', [$horario])) {
                throw new DomainException('O horário está reservado ou já passou.');
            }
            consulta('UPDATE horarios SET disponivel = NOT disponivel WHERE id = ?', [$horario]);
            $conn->commit();
            redirecionar('grade.php', 'Grade atualizada.');
        }
    } catch (DomainException $excecao) {
        $conn->rollback();
        $erro = $excecao->getMessage();
    } catch (mysqli_sql_exception $excecao) {
        $conn->rollback();
        $erro = $excecao->getCode() === 1062 ? 'Já existe um horário cadastrado para esse início.' : 'Não foi possível salvar o horário.';
    }
}
$perfil = linha('SELECT * FROM instrutores WHERE usuario_id = ?', [$id]);
$horarios = linhas('SELECT h.*, a.id AS agendamento FROM horarios h
    LEFT JOIN agendamentos a ON a.horario_id = h.id AND a.reserva = 1
    WHERE h.instrutor_id = ? AND h.inicio > NOW() ORDER BY h.inicio', [$id]);
$titulo = 'Minha grade';
$modulo = true;
require '../sprint1/header.php';
?>
<section class="modulo">
    <h1>Minha grade</h1>
    <p>Cadastre seus dados e publique os horários em que você pode dar aula.</p>
    <?php avisos_pagina($erro); ?>
    <form class="bloco" method="post">
        <?php campo_csrf(); ?><h2>Perfil de instrutor</h2>
        <div class="campos">
            <label>Categoria <select name="categoria"><?php foreach (['A', 'B', 'A/B'] as $opcao): ?>
                <option <?= ($perfil['categoria'] ?? '') === $opcao ? 'selected' : '' ?>><?= $opcao ?></option>
            <?php endforeach; ?></select></label>
            <label>Bairro <input name="bairro" maxlength="80" value="<?= e($perfil['bairro'] ?? '') ?>" required></label>
            <label>Cidade <input name="cidade" maxlength="80" value="<?= e($perfil['cidade'] ?? '') ?>" required></label>
            <label>Valor por aula (R$) <input name="preco" type="number" min="1" max="9999" step="0.01" value="<?= e($perfil['preco'] ?? '') ?>" required></label>
        </div>
        <div class="acoes"><button class="botao" name="acao" value="perfil">Salvar perfil</button></div>
    </form>
    <?php if ($perfil): ?>
    <form class="bloco" method="post">
        <?php campo_csrf(); ?><h2>Novo horário</h2>
        <label>Data e horário <input name="inicio" type="datetime-local" required></label>
        <div class="campos acoes">
            <fieldset><legend>Ponto de encontro</legend>
                <label>Nome do local <input name="ponto_nome" maxlength="150" required></label>
                <label>Latitude do encontro <input id="ponto_lat" name="ponto_lat" inputmode="decimal" required></label>
                <label>Longitude do encontro <input id="ponto_lng" name="ponto_lng" inputmode="decimal" required></label>
                <button class="botao secundario" type="button" data-local="ponto">Usar GPS no encontro</button>
            </fieldset>
            <fieldset><legend>Destino da aula</legend>
                <label>Nome do destino <input name="destino_nome" maxlength="150" required></label>
                <label>Latitude do destino <input id="destino_lat" name="destino_lat" inputmode="decimal" required></label>
                <label>Longitude do destino <input id="destino_lng" name="destino_lng" inputmode="decimal" required></label>
                <button class="botao secundario" type="button" data-local="destino">Usar GPS no destino</button>
            </fieldset>
        </div>
        <p id="mensagem-gps" role="status">Você também pode copiar as coordenadas de um mapa.</p>
        <div class="acoes"><button class="botao" name="acao" value="horario">Publicar horário</button></div>
    </form>
    <?php endif; ?>
    <div class="bloco">
        <h2>Próximos horários</h2>
        <?php if (!$horarios): ?><p>Nenhum horário cadastrado.</p><?php endif; ?>
        <?php foreach ($horarios as $h): ?>
        <div class="linha">
            <p><?= dia_hora($h['inicio']) ?> · <?= e($h['ponto_nome']) ?> → <?= e($h['destino_nome']) ?></p>
            <?php if ($h['agendamento']): ?><p>Reservado</p><?php else: ?>
            <form method="post"><?php campo_csrf(); ?><input type="hidden" name="horario" value="<?= (int) $h['id'] ?>">
                <button class="botao secundario" name="acao" value="disponibilidade"><?= $h['disponivel'] ? 'Ocultar horário' : 'Reabrir horário' ?></button>
            </form><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<script src="script.js"></script>
<?php require '../sprint1/footer.php'; ?>
