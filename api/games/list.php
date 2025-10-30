<?php
ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

require_once(dirname(__DIR__, 2) . '/autoload.php');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


$page = intval($_GET['page'] ?? 1);
$pageSize = intval($_GET['pageSize'] ?? 25);
$search = $_GET['search'] ?? '';

$offset = ($page - 1) * $pageSize;

try {
    $db = (new Database())->getPdo();

    $params = [];
    $sql = "SELECT * FROM Game";
    if ($search) {
        $sql .= " WHERE nome LIKE :search";
        $params[':search'] = "%$search%";
    }

    // Total de registros
    $stmtTotal = $db->prepare($sql);
    $stmtTotal->execute($params);
    $total = $stmtTotal->rowCount();
    $totalPages = ceil($total / $pageSize);

    $sql .= " ORDER BY nome DESC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $msg = new ApiMessage(true, "Lista carregada", ['data' => $data, 'totalPages' => $totalPages]);
    $msg->toJson();
} catch (Exception $e) {
    $msg = new ApiMessage(false, $e->getMessage());
    $msg->toJson();
}
