<?php
ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

require_once __DIR__ . '/autoload.php';

try {
    $db = (new Database())->getPdo();

    // Busca todas as carteiras
    $sql = "SELECT * FROM Carteira";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Atualiza o PlayerId de cada uma
    foreach ($data as $row) 
    {
        $sqlUp = "UPDATE Carteira SET PayerId = :pid WHERE id = :id";
        $stmtUp = $db->prepare($sqlUp);
        $stmtUp->execute([
            ':pid' => 1,
            ':id'  => $row['id']
        ]);
    }

    echo "Atualização concluída com sucesso!";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}

/*
$game = new Game();

$game->createTable();
echo "<br/>table Game ok";

$linkgame = new LinkGame();
$linkgame->createTable();
echo "<br/>table LinkGame ok";

$Provedor = new ProvedorGame();
$Provedor->createTable();
echo "<br/>table Provedor ok";

$Carteira = new Carteira();
$Carteira->createTable();
echo "<br/>table carteira ok";

$GestaoHistorico = new GestaoHistorico();
$GestaoHistorico->createTable();
echo "<br/>table gestao histórico ok";

$GestaoConfig = new GestaoConfig();
$GestaoConfig->createTable();
echo "<br/>table gestao ok";

*/






/**/
echo "<br/>ok";





?>
