<?php
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../ApiMessage.php';

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);

if ($id <= 0) {
    (new ApiMessage(false, "ID inválido"))->toJson();
}

try {
    $db = (new Database())->getPdo();
    $stmt = $db->prepare("DELETE FROM games WHERE id=:id");
    $stmt->execute(['id' => $id]);

    (new ApiMessage(true, "Game deletado com sucesso"))->toJson();
} catch (Exception $e) {
    (new ApiMessage(false, $e->getMessage()))->toJson();
}
