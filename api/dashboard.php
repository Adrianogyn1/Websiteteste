<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



require_once(dirname(__DIR__, 1) . '/autoload.php');


if (session_status() == PHP_SESSION_NONE) {
    session_start();
};

if (!isset($_SESSION['user'])) {
    (new ApiMessage(false, 'Usuário não logado'))->toJson();
}

$userId = $_SESSION['id'] ?? 0;

header('Content-Type: application/json; charset=utf-8');

$carteiraId = isset($_GET['carteiraId']) ? intval($_GET['carteiraId']) : null;

try {
    $db = (new Database())->getPdo();
    $dash = new Dashboard($db, $userId,$carteiraId);
    $dash->carregar(); // Carrega últimos 7 dias
    echo json_encode(['success' => true, 'msg' => 'Dashboard carregado', 'data' => json_decode($dash->toJson())]);
} catch (Exception $e) 
{
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
