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
   // $avatar = $json['avatar'] ?? '';
    $userId = $_SESSION['user'] ?? 0; 
    $user =  User::find($userId);

   $user->avatar =json_encode($json);
   $user->save();
   $msg->data=$json;
    
   $msg->sucess=true;
    $msg->msg = "salvo com sucesso ";
    

    } catch (Throwable $err) {
        $msg->msg = "Erro interno no servidor. ".$err->getMessage();
        // opcional: $msg->data = ['error' => $err->getMessage()];
    }
   }
   
   
   $msg->toJson();
   ?>