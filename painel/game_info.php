<?php include __DIR__.'/includes/header.php'; ?>
<?php include __DIR__.'/includes/menu.php'; ?>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(dirname(__DIR__, 1) . '/autoload.php');




$gameId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($gameId <= 0) die('Game inválido.');

$db = (new Database())->getPdo();

// Filtrar período
$startDate = isset($_GET['start']) ? $_GET['start'] : null;
$endDate   = isset($_GET['end']) ? $_GET['end'] : null;

// Buscar info do game
$stmt = $db->prepare("SELECT * FROM Game WHERE id = :id");
$stmt->execute(['id' => $gameId]);
$gameData = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$gameData) die('Game não encontrado.');

// Buscar histórico de pagamentos
$sql = "SELECT * FROM PaymanetHistorico WHERE gameId = :gameId";
$params = ['gameId' => $gameId];
if ($startDate && $endDate) {
    $sql .= " AND data BETWEEN :start AND :end";
    $params['start'] = $startDate . ' 00:00:00';
    $params['end']   = $endDate . ' 23:59:59';
}
$sql .= " ORDER BY data ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hist = [];
$chartLabels = [];
$chartData = [];

foreach ($rows as $r) {
    $ph = new PaymanetHistorico();
    $ph->id = (int)$r['id'];
    $ph->playerId = (int)$r['playerId'];
    $ph->gameId = (int)$r['gameId'];
    $ph->gameNome = $r['gameNome'];
    $ph->carteiraId = (int)$r['carteiraId'];
    $ph->valor = (float)$r['valor'];
    $ph->bancaInicio = (float)$r['bancaInicio'];
    $ph->bancaFinal = (float)$r['bancaFinal'];
    $ph->tempo = (float)$r['tempo'];
    //$ph->data = new DateTime($r['data']);
    //$ph->dataCriacao = new DateTime($r['dataCriacao']);
    $ph->dificuldade = GameDificuldade::from((int)$r['dificuldade']);
    $ph->type = TransasaoType::from((int)$r['type']);

    $hist[] = $ph;
    $chartLabels[] = $ph->data->format('d/m/Y H:i');
    $chartData[] = $ph->valor ;// $ph->bancaFinal;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Histórico do Game: <?= htmlspecialchars($gameData['nome']) ?></title>

<style>
img.game-img { max-width: 150px; height: auto; display:block; margin-bottom: 10px; }
.btn-group { display: flex; gap: 5px; flex-wrap: wrap; margin-bottom: 15px; }
</style>
</head>
<body class="bg-light">
<div class="container py-4">
    <h3 class="mb-3">Histórico do Game: <?= htmlspecialchars($gameData['nome']) ?></h3>

    <div class="mb-3">
        <img src="<?= htmlspecialchars($gameData['image']) ?>" alt="<?= htmlspecialchars($gameData['nome']) ?>" class="game-img">
        <div class="btn-group">
            <a href="<?= htmlspecialchars($gameData['demo']) ?>" target="_blank" class="btn btn-sm btn-success">Demo</a>
            <a href="<?= htmlspecialchars($gameData['url']) ?>" target="_blank" class="btn btn-sm btn-primary">Jogar</a>
            <a href="games.php" class="btn btn-sm btn-secondary">Voltar</a>
        </div>
    </div>

    <!-- Filtro por período -->
    <form method="get" class="mb-3">
        <input type="hidden" name="id" value="<?= $gameId ?>">
        <input type="text" name="daterange" id="daterange" class="form-control" placeholder="Selecione o período" />
    </form>

    <!-- Gráfico -->
    <canvas id="bancaChart" height="100"></canvas>

    <?php if (count($hist) === 0): ?>
        <div class="alert alert-info mt-3">Nenhum histórico encontrado para este jogo.</div>
    <?php else: ?>
        <table class="table table-striped table-bordered mt-3">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Banca Inicial</th>
                    <th>Banca Final</th>
                    <th>Dificuldade</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($hist as $h): ?>
                <tr>
                    <td><?= $h->data->format('d/m/Y H:i') ?></td>
                    <td><?= $h->type->name ?></td>
                    <td><?= number_format($h->valor, 2, ',', '.') ?></td>
                    <td><?= number_format($h->bancaInicio, 2, ',', '.') ?></td>
                    <td><?= number_format($h->bancaFinal, 2, ',', '.') ?></td>
                    <td><?= $h->dificuldade->name ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
const ctx = document.getElementById('bancaChart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Banca Final',
            data: <?= json_encode($chartData) ?>,
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.3,
            fill: true
        }]
    },
    options: {
        responsive: true,
        scales: { x: { display: true }, y: { display: true } }
    }
});

// Daterange
$(function() {
    $('#daterange').daterangepicker({
        locale: { format: 'YYYY-MM-DD' },
        opens: 'left'
    }, function(start, end, label) {
        window.location.href = '?id=<?= $gameId ?>&start=' + start.format('YYYY-MM-DD') + '&end=' + end.format('YYYY-MM-DD');
    });
});
</script>
<?php include __DIR__.'/includes/footer.php'; ?>