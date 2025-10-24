<?php
ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

require_once(dirname(__DIR__, 2) . '/autoload.php');
session_start();


$page = intval($_GET['page'] ?? 1);
$pageSize = intval($_GET['pageSize'] ?? 25);
$search = $_GET['search'] ?? '';
$carteiraId =2;

$offset = ($page - 1) * $pageSize;

try {
    $db = (new Database())->getPdo();

    $params = [];
    $sql = "SELECT * FROM PaymanetHistorico";
    if ($search) {
        $sql .= " WHERE game LIKE :search";
        $params[':search'] = "%$search%";
        $sql .= " AND carteiraId = :carteira";
        $params[':carteira'] = $carteiraId;
    }
    else{
        $sql .= " where carteiraId = :carteira";
        $params[':carteira'] = $carteiraId;
    }

    // Total de registros
    $stmtTotal = $db->prepare($sql);
    $stmtTotal->execute($params);
    $total = $stmtTotal->rowCount();
    $totalPages = ceil($total / $pageSize);

    $sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data as $row)
    {
        $row['data'] = Helps::mysqlTicksToDateTime($row['data'] );
    }
    $carteira = new Carteira();
   $carteira= $carteira->read($carteiraId);
     $fim = new \DateTime();
    $inicio = (clone $fim)->modify('-7 days');
     
    $saida =$carteira->GetRetiradas($inicio,$fim);
    $saldo=$carteira->GetSaldo();
    $entradas=$carteira->GetDepositos($inicio,$fim);

    $msg = new ApiMessage(true, "Lista carregada", ['data' => $data,'saldo'=>$saldo,'entradas' =>$entradas, 'saidas'=>$saida, 'totalPages' => $totalPages]);
    $msg->toJson();
} catch (Exception $e) {
    $msg = new ApiMessage(false, $e->getMessage());
    $msg->toJson();
}
