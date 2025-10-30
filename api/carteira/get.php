<?php
ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

require_once(dirname(__DIR__, 2) . '/autoload.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica login
if (!isset($_SESSION['user'])) {
    (new ApiMessage(false, 'Usuário não logado'))->toJson();
}

$userId = $_SESSION['user'] ?? 0;



$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    (new ApiMessage(false, "ID inválido"))->toJson();
}

try {
    $db = (new Database())->getPdo();
  /*  
    $stmt = $db->prepare("SELECT * FROM Carteira WHERE id=:id");
    $stmt->execute(['id' => $id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
*/
$cart = Carteira::find($db,$id);

    $msg = new ApiMessage(true, $cart ? "carteira encontrado" : "Game não encontrado", $cart);
    $msg->toJson();
} catch (Exception $e) {
    (new ApiMessage(false, $e->getMessage()))->toJson();
}
