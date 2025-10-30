<?php
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
require_once(dirname(__DIR__, 2) . '/autoload.php');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica login
if (!isset($_SESSION['user'])) {
    (new ApiMessage(false, 'Usuário não logado'))->toJson();
}

$userId = $_SESSION['user'] ?? 0;


$page = intval($_GET['page'] ?? 1);
$pageSize = intval($_GET['pageSize'] ?? 25);
$search = $_GET['search'] ?? '';

$offset = ($page - 1) * $pageSize;

$db = (new DataBase())->getPdo();

$carteira = new Carteira();
$carteira->PayerId=$userId;

//$carts=$carteira->all($db);
$carteiras=$carteira->allUser($carteira->PayerId);

$msg = new ApiMessage(true, "Lista carregada", [
    'data' => array_map(fn($c) => $c->toArray(), $carteiras), // converte objetos para array
]);
$msg->toJson();
exit;


try {
    
  /*  $carteira = new Carteira();
    $carteira->PayerId= $userId;
    $all = $carteira->all();
    
    $msg = new ApiMessage(true, "",$all);
    $msg->toJson();
    
    */
    $db = (new Database())->getPdo();

    $params = [];
    $where = '';
    
    if ($search) {
        $where = " WHERE nome LIKE :search AND PayerId = :id";
        $params[':search'] = "%$search%";
        $params[':id']=$userId;
    }else{
        $where ='WHERE PayerId = :id';
        $params[':id']=$userId;
    }

    // Total de registros
    $stmtTotal = $db->prepare("SELECT COUNT(*) as total FROM Carteira $where");
    $stmtTotal->execute($params);
    $totalRows = (int)$stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRows / $pageSize);

    // Buscar registros da página
    $sql = "SELECT id FROM Carteira $where ORDER BY id DESC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $carteiras = [];
    //completa dados
    foreach ($data as $row) 
    {
        $carteira = new Carteira();
        $carteira->read($row['id']); 
        $carteira->GetSaldo();//atualizar o saldo
        $carteiras[] = $carteira;
    }

$msg = new ApiMessage(true, "Lista carregada", [
    'data' => array_map(fn($c) => $c->toArray(), $carteiras), // converte objetos para array
    'totalPages' => $totalPages,
    'totalRows' => $totalRows,
    'currentPage' => $page
]);

    $msg->toJson();
    

} catch (Exception $e) {
    $msg = new ApiMessage(false, $e->getMessage());
    $msg->toJson();
}
