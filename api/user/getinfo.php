<?php

ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

require_once(dirname(__DIR__, 2) . '/autoload.php');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$msg = new ApiMessage(); // inicializa padrão: sucess=false, msg='', data=null
$msg->msg="erro!";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
   try {
   $json = json_decode(file_get_contents('php://input'), true);
    $id = $json['id'] ?? '';
    

        $user = User::find($id);
            
            $msg->sucess = true;
            $msg->msg = "";
            $msg->data = $user; // opcional: retorna dados do usuário

    
    } catch (Throwable $err) {
        $msg->msg = "Erro interno no servidor.";
        // opcional: $msg->data = ['error' => $err->getMessage()];
    }
   }
   else
   {
      try {
        $db = new Database();

        $stmt = $db->query("SELECT * FROM users WHERE id = ?", [$_SESSION['id']]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            $msg->msg = "Usuário não encontrado.";
            $msg->toJson();
        }

        $user = User::createFromArray($data);
            
            $msg->sucess = true;
            $msg->msg = "";
            $msg->data = $user; // opcional: retorna dados do usuário


    } catch (Throwable $err) {
        $msg->msg = "Erro interno no servidor.";
        // opcional: $msg->data = ['error' => $err->getMessage()];
    }
   }
   
   
   $msg->toJson();
   ?>