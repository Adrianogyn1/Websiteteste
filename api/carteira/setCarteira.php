<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(dirname(__DIR__, 2) . '/autoload.php');

session_start();

// Verifica login
if (!isset($_SESSION['user'])) {
    (new ApiMessage(false, 'Usuário não logado'))->toJson();
}

$userId = $_SESSION['id'] ?? 0;

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    $selected =isset($input['selected']) ? (bool)$input['selected'] : false;;
        
    


    
    

    $db = (new Database())->getPdo();


    

    if ($id > 0) {
        
        $sql = "UPDATE Carteira SET selected = 0 WHERE PayerId = :id";
$stmt = $db->getPdo()->prepare($sql);
$stmt->execute([':id' => $userId]);
//atualizar essa
         $sql = "UPDATE Carteira SET selected = 1 WHERE id = :id";
$stmt = $db->getPdo()->prepare($sql);
$stmt->execute([':id' => $id]);

    }
        
    (new ApiMessage(true, 'Carteira atualizada com sucesso',$carteira))->toJson();
        

} catch (Exception $e) {
    (new ApiMessage(false, $e->getMessage()))->toJson();
}
