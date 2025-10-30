<?php
// /app/api/carteira/save.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(dirname(__DIR__, 2) . '/autoload.php');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// O PayerId virá da sessão, se estiver configurado
$userId = $_SESSION['user'] ?? 0; 

// --- VERIFICAÇÃO DE LOGIN ---
// Se não estiver logado OU o ID da sessão for inválido
if (!isset($_SESSION['user']) || $userId <= 0) {
    (new ApiMessage(false, 'Sessão inválida. Usuário não logado.'))->toJson();
}

try {
    // 1. LER E DECODIFICAR O JSON DA REQUISIÇÃO
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Se o input não for um array ou for vazio, há um erro na requisição
    if (!is_array($input)) {
         (new ApiMessage(false, 'Dados de requisição inválidos (não é JSON).'))->toJson();
    }
    
    // 2. EXTRAIR E SANITIZAR OS CAMPOS
    $id = intval($input['id'] ?? 0);
    $nome = trim($input['nome'] ?? '');
    
    // Campos opcionais/específicos:
    $meta = trim($input['meta'] ?? '');
    $url = trim($input['url'] ?? '');
    $login = trim($input['login'] ?? '');
    $senha = trim($input['senha'] ?? '');
    
    // Campo booleano (checkbox): Envia '1' se marcado, ou é ausente se desmarcado.
    // Usamos intval para ter certeza que é 0 ou 1, que será tratado pelo __set
    $useRelatorio = intval($input['useRelatorio'] ?? 0); 
    
    // O campo 'selected'
    $selected = isset($input['selected']) ? (bool)$input['selected'] : false;

    // --- VALIDAÇÕES BÁSICAS ---
    if (!$nome) {
        (new ApiMessage(false, 'O nome da carteira é obrigatório.'))->toJson();
    }
    
    $db = (new Database())->getPdo();

    // 3. VERIFICAR LIMITE DE CARTEIRAS (APENAS PARA NOVAS CRIAÇÕES)
    if ($id === 0) {
        
        $stmtCount = $db->prepare("SELECT COUNT(*) as total FROM Carteira WHERE PayerId = :uid");
        $stmtCount->execute([':uid' => $userId]);
        $total = (int)$stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
        if ($total >= 10) {
            (new ApiMessage(false, 'Limite de 10 carteiras atingido. Exclua uma para adicionar uma nova.'))->toJson();
        }
    }
    
    // 4. LER OU CRIAR O OBJETO CARTEIRA
    $carteira = new Carteira();
    if ($id > 0) {
        // Se for edição, use o método read. 
        // Assumimos que read() preenche o objeto ou falha/deixa $carteira->id=0 se não encontrar.
        if (!$carteira->read($id) || $carteira->id === 0) {
             (new ApiMessage(false, 'Carteira não encontrada para edição (ID: ' . $id . ').'))->toJson();
        }
    } 
    
    // 5. ATUALIZAR O OBJETO CARTEIRA com os dados do INPUT
    // O uso da sintaxe de propriedade direta (->propriedade) invoca o __set() da classe Carteira.

    $carteira->nome = $nome;
    $carteira->PayerId = $userId; // ID do usuário que está salvando
    $carteira->useRelatorio = $useRelatorio;
    $carteira->selected = $selected;

    // Campos que podem ser vazios ou opcionais, mas que o __set pode tratar.
    $carteira->meta = $meta;
    $carteira->url = $url;
    $carteira->login = $login;

    // A senha SÓ deve ser atualizada se um novo valor foi fornecido.
    if (!empty($senha)) {
        // O __set() da classe Carteira deve aplicar o password_hash() aqui.
        $carteira->senha = $senha;
    }
        
    // 6. SALVAR
    $carteira->save();
        
    // 7. RETORNO DA API
    // Retorna o objeto (já com o novo ID/data de update) como array para o JSON
    (new ApiMessage(true, 'Carteira salva com sucesso.', $carteira->toArray()))->toJson();
        

} catch (Exception $e) {
    // Tratamento de erros de banco de dados, __set() ou outras exceções
    (new ApiMessage(false, 'Erro ao processar a requisição: ' . $e->getMessage()))->toJson();
}