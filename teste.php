<?php

require_once('autoload.php');

$db = (new DataBase())->getPdo();

$carteira = new Carteira();
$carteira->PayerId=1;

//$carts=$carteira->all($db);
$carts=Carteira::allUser(1);

$inicio = new DateTime('2024-10-01 00:00:00');
$fim = new DateTime('2025-10-30 23:59:59'); // Inclui o dia 30 inteiro
foreach ($carts as $value) {
    echo json_encode($value->getHistorico(/*$inicio,$fim,20,0*/));
  //  echo $value->nome.' R$ '.$value->saldo.", ";
}

echo Carteira::getLastRawSql();
//$dados= Carteira::all();
//$carteira->exporta('sql','ambos','data.txt');
//echo $sql;

/*$carteira->nome ="test php";


$carteira->save();*/

echo json_encode(['carteira'=>$carteira, 'raw'=> Carteira::getLastRawSql()]);
 



exit;


$user = new User();
$user->nome='teste';
$user->email='teste@gmail.com';
$user->senha='12345';
//$user->cadastrar();

//User::findByEmail("adrianogyn@gmail.com");

echo json_encode(['user'=>$user, 'raw'=> User::getLastRawSql(), 'login'=>$user->login('12345')]);



exit;



// --- Configuração e Carga (Simulação de Autoload e Namespace) ---

// Supondo que você tem um autoloader (Composer), você só precisaria usar os namespaces:
//use App\Model\Inbox;
//use App\Model\Visualizacao;
//use PDO;

// Simulação da conexão PDO (SQLite em memória)
$db = (new DataBase())->getPdo();

echo "--- 1. CRIAÇÃO DAS TABELAS (Teste de createTable e IgnoreInDatabase) ---\n";
Inbox::createTable($db); 


// Verifica se a coluna ignorada foi realmente omitida na tabela 'inbox'
$inboxCols = $db->query("PRAGMA table_info(inbox)")->fetchAll(PDO::FETCH_COLUMN, 1);
echo "Colunas de 'inbox' no BD: " . implode(', ', $inboxCols) . "\n";
// SAÍDA ESPERADA: Não deve incluir 'logEnvio'

echo "\n--- 2. INSERÇÃO (Teste de CREATE e bool=false) ---\n";

$msg = new Inbox($db);
$msg->populate([
    'remetente' => 'sistema@exemplo.com',
    'assunto' => 'Nova Notificação'
]);
$msg->logEnvio = 'Log: Ignorar este texto'; // Este campo será ignorado no BD
$msg->save();

echo "Mensagem ID: " . $msg->id . " criada.\n";
// Verifica no BD se o campo lida (bool=false) foi salvo como 0
$dataBD = $db->query("SELECT * FROM inbox WHERE id = {$msg->id}")->fetch(PDO::FETCH_ASSOC);
echo "lida no BD (deve ser 0): " . $dataBD['lida'] . "\n";


echo "\n--- 3. ATUALIZAÇÃO (Teste de UPDATE inteligente e NULL-Ignorado) ---\n";

// Busca o objeto
$msgUpdate = Inbox::find($db, $msg->id); 
echo "Status 'lida' inicial: " . ($msgUpdate->lida ? 'TRUE' : 'FALSE') . "\n";

// Altera o booleano para true
$msgUpdate->lida = true;

// Define o remetente como NULL (DEVE ser ignorado)
$msgUpdate->remetente = null; 

$msgUpdate->remetente=$msgUpdate->id."@email";

// Define o campo ignorado (deve ser ignorado)
$msgUpdate->logEnvio = 'Novo log temporário'; 

$msgUpdate->save();

// Busca o objeto ATUALIZADO
$msgFinal = Inbox::find($db, $msg->id); 

echo "Status 'lida' após update: " . ($msgFinal->lida ? 'TRUE' : 'FALSE') . " (OK - TRUE)\n";
echo "Remetente após update: " . $msgFinal->remetente . " (OK - NULL Ignorado, valor original mantido)\n";

$all = Inbox::all($db); 
foreach ($all as $value) {
 echo '<br\>nremetente'.$value->remetente." | ".$value->assunto;
}

echo "\n--- 4. TESTE DE DELETE ---\n";
//if ($msgFinal->delete()) {
    //echo "Registro ID {$msgFinal->id} deletado com sucesso.\n";
//}