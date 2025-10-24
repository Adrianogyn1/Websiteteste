<?php
ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

require_once(dirname(__DIR__, 1) . '/autoload.php');
session_start();


use Cassino\GameDificuldade;
use Cassino\TransasaoType;
use Cassino\PaymanetHistorico;

// Pegar ID do game
$gameId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($gameId <= 0) {
    die('Game inválido.');
}

$db = Database::getInstance()->getPdo();

// Buscar info do game
$stmt = $db->prepare("SELECT * FROM games WHERE id = :id");
$stmt->execute(['id' => $gameId]);
$gameData = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$gameData) die('Game não encontrado.');

// Buscar histórico de pagamentos
$stmt = $db->prepare("SELECT * FROM payment WHERE gameId = :gameId ORDER BY data ASC");
$stmt->execute(['gameId' => $gameId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Criar objetos PaymanetHistorico
$hist = [];
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
    $ph->data = new DateTime($r['data']);
    $ph->dataCriacao = new DateTime($r['dataCriacao']);
    $ph->dificuldade = GameDificuldade::from((int)$r['dificuldade']);
    $ph->type = TransasaoType::from((int)$r['type']);

    $hist[] = $ph;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Histórico do Game: <?= htmlspecialchars($gameData['nome']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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

    <?php if (count($hist) === 0): ?>
        <div class="alert alert-info">Nenhum histórico encontrado para este jogo.</div>
    <?php else: ?>
        <table class="table table-striped table-bordered">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
