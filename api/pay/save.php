<?php
// Configuração de ambiente
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Carregamento de classes e Enums
require_once(dirname(__DIR__, 2) . '/autoload.php'); 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Inicializa a mensagem de API (boa prática)
$msg = null;

try 
{

    // --- 1. RECEBIMENTO E DECODIFICAÇÃO DO JSON ---
    $input = json_decode(file_get_contents('php://input'), true);

    // --- 2. DEFINIÇÃO DAS VARIÁVEIS (Mantendo seus nomes e lógica) ---
    $id = intval($input['id'] ?? 0);
    $gameNome = $input['gameNome'] ?? '';
    $gameId = intval($input['gameId'] ?? 0);
    $type = $input['type'] ?? ''; // Valor string (ex: 'DEPOSITO')
    $tempo = floatval($input['tempo'] ?? 0);
    $valor = floatval($input['valor'] ?? 0);
    
    // Valores essenciais que devem ser obtidos do contexto (Sessão/Auth)

    $userId = $_SESSION['user'] ?? 0; 
    $carteiraId = (new Carteira())->GetSelected($userId)->id??0;  //busca no banco
    
    // Verifica se o usuário e a carteira são válidos antes de prosseguir
    if ($userId === 0 || $carteiraId === 0) {
        $msg = new ApiMessage(false, 'Usuário ou Carteira não identificados. Faça login novamente.');
        $msg->toJson();
        exit();
    }
    
    
    // --- 3. VALIDAÇÃO DE TIPO DE TRANSAÇÃO (ENUM) ---
    try {
        $transacaoType = TransasaoType::from(strtoupper($type));
    } catch (ValueError $e) {
        $msg = new ApiMessage(false, 'Tipo de transação inválido.', ['details' => $e->getMessage()]);
        $msg->toJson();
        exit();
    }

    // --- 4. PREENCHIMENTO E LEITURA ---
    $transacao = new PaymanetHistorico();

    if ($id > 0) {
        // Tentamos carregar a transação existente (UPDATE)
        $loaded = $transacao->read($id);
        if ($loaded === null) {
            $msg = new ApiMessage(false, 'Transação não encontrada para o ID fornecido.', ['id' => $id]);
            $msg->toJson();
            exit();
        }
        // Se read retornar um objeto, $transacao agora contém os dados lidos
    } else {
        // Se não há ID (CREATE), definimos as datas para agora
        $transacao->dataCriacao = new DateTime();
        $transacao->data = new DateTime();
    }

    // --- 5. POPULANDO AS VARIÁVEIS DO OBJETO ---
    
    $transacao->playerId = $userId;
    $transacao->gameNome = $gameNome;
    $transacao->gameId = $gameId;
    $transacao->tempo = $tempo;
    $transacao->valor = $valor;
    $transacao->carteiraId = $carteiraId;
    $transacao->type = $transacaoType; 
    
    // Ajusta GameID/GameNome para Depósito/Saque (se não for aplicável)
    if (in_array($transacaoType->value, ['DEPOSITO', 'SAQUE'])) {
        $transacao->gameId = 0;
        $transacao->gameNome = '';
    }


    // --- 6. VALIDAÇÃO E SALVAMENTO ---
   /* if ($valor <= 0) {
        $msg = new ApiMessage(false, 'O valor da transação deve ser positivo.');
        $msg->toJson();
        exit();
    }*/

    $success = $transacao->save();

    // --- 7. RESPOSTA DA API ---
    if ($success) {
        $action = $id > 0 ? 'atualizada' : 'criada';
        $msg = new ApiMessage(true, "Transação {$action} com sucesso.", [
            'id' => $transacao->id, 
            'dados_salvos' => $transacao->toArray()
        ]);
    } else {
        $msg = new ApiMessage(false, 'Erro ao salvar o histórico no banco de dados. (Verifique sua conexão e queries na classe PaymanetHistorico)');
    }

    $msg->toJson();

} catch (Exception $e) {
    // Captura qualquer exceção geral (ex: erro de PDO, erro de classe não encontrada)
    $msg = new ApiMessage(false, $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    $msg->toJson();
}
?>