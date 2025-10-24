<?php
ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

require_once(dirname(__DIR__, 2) . '/autoload.php');
session_start();


$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);
$nome = $input['nome'] ?? '';
$url = $input['url'] ?? '';
$demo = $input['demo'] ?? '';
$image = $input['image'] ?? '';

try {
    $db = (new Database())->getPdo();

    if ($id > 0) {
        $stmt = $db->prepare("UPDATE games SET nome=:nome, url=:url, demo=:demo, image=:image WHERE id=:id");
        $stmt->execute(compact('nome', 'url', 'demo', 'image', 'id'));
        $msg = new ApiMessage(true, "Game atualizado com sucesso");
    } else {
        $stmt = $db->prepare("INSERT INTO games (nome, url, demo, image) VALUES (:nome, :url, :demo, :image)");
        $stmt->execute(compact('nome', 'url', 'demo', 'image'));
        $msg = new ApiMessage(true, "Game criado com sucesso", ['id' => $db->lastInsertId()]);
    }

    $msg->toJson();
} catch (Exception $e) {
    $msg = new ApiMessage(false, $e->getMessage());
    $msg->toJson();
}
