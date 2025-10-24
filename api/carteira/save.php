<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(dirname(__DIR__, 2) . '/autoload.php');
require_once(dirname(__DIR__, 2) . '/classes/ApiMessage.php');
require_once(dirname(__DIR__, 2) . '/classes/Carteira.php');

session_start();

// Verifica login
if (!isset($_SESSION['user'])) {
    (new ApiMessage(false, 'Usuário não logado'))->toJson();
}

$userId = $_SESSION['user']['id'] ?? 0;

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    $nome = trim($input['nome'] ?? '');
    $meta = trim($input['meta'] ?? '');
    $tipo = trim($input['tipo'] ?? '');
    $saldo = floatval($input['saldo'] ?? 0);
    $teste = isset($input['teste']) ? (bool)$input['teste'] : false;

    if (!$nome ) {
        (new ApiMessage(false, 'Nome  obrigatório'))->toJson();
    }

    $db = (new Database())->getPdo();

    // Limite de 10 carteiras por usuário
    if ($id === 0) {
        $stmtCount = $db->prepare("SELECT COUNT(*) as total FROM carteira WHERE user_id = :uid");
        $stmtCount->execute([':uid' => $userId]);
        $total = (int)$stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
        if ($total >= 10) {
            (new ApiMessage(false, 'Você já possui 10 carteiras'))->toJson();
        }
    }

    if ($id > 0) {
        // Atualizar carteira existente
        $stmt = $db->prepare("
            UPDATE carteira SET 
                nome = :nome, 
                meta = :meta, 
                
                saldo = :saldo, 
                teste = :teste, 
                updated_at = NOW()
            WHERE id = :id AND user_id = :uid
        ");
        $stmt->execute([
            ':nome' => $nome,
            ':meta' => $meta,
            //':tipo' => $tipo,
            ':saldo' => $saldo,
            ':teste' => $teste,
            ':id' => $id,
            ':uid' => $userId
        ]);

        (new ApiMessage(true, 'Carteira atualizada com sucesso'))->toJson();
    } else {
        // Criar nova carteira
        $stmt = $db->prepare("
            INSERT INTO carteira 
                (user_id, nome, meta, saldo, teste, created_at, updated_at) 
            VALUES 
                (:uid, :nome, :meta, :saldo, :teste, NOW(), NOW())
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':nome' => $nome,
            ':meta' => $meta,
          //  ':tipo' => $tipo,
            ':saldo' => $saldo,
            ':teste' => $teste
        ]);

        $novoId = $db->lastInsertId();
        (new ApiMessage(true, 'Carteira criada com sucesso', ['id' => $novoId]))->toJson();
    }

} catch (Exception $e) {
    (new ApiMessage(false, $e->getMessage()))->toJson();
}
