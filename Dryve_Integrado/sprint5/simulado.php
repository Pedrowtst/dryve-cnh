<?php
require_once '../app.php';
$questoes = require 'questoes.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    if (campo('acao') === 'iniciar') {
        $minutos = campo('tempo') === '120' ? 120 : 60;
        $ordem = [];
        foreach ($questoes as $i => $questao) {
            $ordem[$i] = array_keys($questao[1]);
            shuffle($ordem[$i]);
        }
        $_SESSION['simulado'] = ['fim' => time() + $minutos * 60, 'ordem' => $ordem];
        unset($_SESSION['resultado_simulado']);
        header('Location: simulado.php', true, 303);
        exit;
    }
    if (campo('acao') === 'corrigir' && isset($_SESSION['simulado'])) {
        $enviadas = is_array($_POST['respostas'] ?? null) ? $_POST['respostas'] : [];
        $prazoVencido = time() > $_SESSION['simulado']['fim'] + 10;
        $respostas = [];
        $acertos = 0;
        foreach ($questoes as $i => $questao) {
            $resposta = $enviadas[$i] ?? null;
            if ($prazoVencido || !in_array($resposta, ['0', '1', '2', '3'], true)) continue;
            $respostas[$i] = $resposta;
            if ($resposta === (string) $questao[2]) $acertos++;
        }
        $_SESSION['resultado_simulado'] = ['acertos' => $acertos, 'respostas' => $respostas, 'prazo_vencido' => $prazoVencido];
        unset($_SESSION['simulado']);
        header('Location: simulado.php', true, 303);
        exit;
    }
}
$prova = $_SESSION['simulado'] ?? null;
$resultado = $_SESSION['resultado_simulado'] ?? null;
$titulo = 'Simulado teórico';
$modulo = true;
require '../sprint1/header.php';
?>
<section class="modulo">
    <h1>Simulado teórico</h1>
    <p><a href="index.php">Voltar aos estudos</a></p>
    <p>Pratique com 30 questões de múltipla escolha. A meta é acertar pelo menos 20.</p>
    <?php if ($resultado): ?>
    <div class="bloco">
        <h2>Resultado</h2>
        <?php if (!empty($resultado['prazo_vencido'])): ?>
            <p>O tempo acabou antes do envio das respostas. Você pode começar outro simulado.</p>
        <?php else: ?>
            <p><strong><?= (int) $resultado['acertos'] ?> de <?= count($questoes) ?> acertos</strong> · <?= $resultado['acertos'] >= 20 ? 'Meta atingida' : 'Continue praticando' ?></p>
        <?php endif; ?>
        <details><summary>Ver correção</summary>
            <ol><?php foreach ($questoes as $i => [$enunciado, $alternativas, $correta]):
                $marcada = $resultado['respostas'][$i] ?? null; ?>
                <li class="linha"><strong><?= e($enunciado) ?></strong>
                    <p>Sua resposta: <?= e(in_array($marcada, ['0','1','2','3'], true) ? $alternativas[(int) $marcada] : 'Em branco') ?></p>
                    <p>Correta: <?= e($alternativas[$correta]) ?></p>
                </li>
            <?php endforeach; ?></ol>
        </details>
    </div>
    <?php endif; ?>
    <?php if ($prova): ?>
        <p>Tempo restante <strong id="tempo-restante"></strong></p>
        <form method="post" id="simulado-form" data-segundos="<?= max(0, $prova['fim'] - time()) ?>">
            <?php campo_csrf(); ?><input type="hidden" name="acao" value="corrigir">
            <?php foreach ($questoes as $i => [$enunciado, $alternativas]): ?>
                <fieldset class="bloco questao"><legend><?= $i + 1 ?>. <?= e($enunciado) ?></legend>
                    <?php foreach ($prova['ordem'][$i] ?? array_keys($alternativas) as $n): ?>
                    <label><input type="radio" name="respostas[<?= $i ?>]" value="<?= $n ?>"><?= e($alternativas[$n]) ?></label>
                    <?php endforeach; ?>
                </fieldset>
            <?php endforeach; ?>
            <button class="botao">Entregar e corrigir</button>
        </form>
        <script src="sprint5.js"></script>
    <?php else: ?>
    <form class="bloco" method="post">
        <?php campo_csrf(); ?>
        <label>Duração <select name="tempo"><option value="60">60 minutos</option><option value="120">120 minutos</option></select></label>
        <div class="acoes"><button class="botao" name="acao" value="iniciar"><?= $resultado ? 'Refazer simulado' : 'Iniciar simulado' ?></button></div>
    </form>
    <?php endif; ?>
</section>
<?php require '../sprint1/footer.php'; ?>
