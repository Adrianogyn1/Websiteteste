<?php

ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

require_once(dirname(__DIR__, 2) . '/autoload.php');
session_start();

$msg = new ApiMessage(); // inicializa padrão: sucess=false, msg='', data=null
$msg->msg="erro!";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
   try {
    $json = json_decode(file_get_contents('php://input'), true);
    $nome = $json['nome'] ?? '';
    $email = $json['email'] ?? '';
    $senha = $json['senha'] ?? '';

    if (!$nome || !$email || !$senha) {
        
        $msg->msg= 'Preencha todos os campos';
        $msg->toJson();
    }

    
    
    $user = new User();
    $user.create($nome,$email,$senha);
    $user.save();
    
        
       
    } catch (Throwable $e) 
    {
        
        $msg->msg=  'Erro ao cadastrar usuário'.$err->getMessage();
    }

}

$msg->toJson();