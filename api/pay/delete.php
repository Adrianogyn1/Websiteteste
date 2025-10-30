<?php
ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

require_once(dirname(__DIR__, 2) . '/autoload.php');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);

if ($id <= 0) {
    (new ApiMessage(false, "ID inválido"))->toJson();
}

try {
    $db = (new Database())->getPdo();
    $stmt = $db->prepare("DELETE FROM PaymanetHistorico WHERE id=:id");
    $stmt->execute(['id' => $id]);

    (new ApiMessage(true, "Histórico deletado com sucesso"))->toJson();
} catch (Exception $e) {
    (new ApiMessage(false, $e->getMessage()))->toJson();
}
