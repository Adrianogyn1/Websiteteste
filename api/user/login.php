<?php

try{
require_once(dirname(__DIR__, 2) . '/autoload.php');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$msg = new ApiMessage(); // inicializa padrão: sucess=false, msg='', data=null


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $json = json_decode(file_get_contents('php://input'), true);
    $email = trim($json['email'] ?? '');
    $senha = trim($json['senha'] ?? '');

    if (empty($email) || empty($senha)) {
        $msg->msg = "Preencha todos os campos.";
        $msg->toJson();
    }

    try {
        $db = new Database();

$user = new User();
$user->email=$email;


/*if (!$user) {
            $msg->msg = "Usuário não encontrado.";
            $msg->toJson();
        }*/

        if ($user->login($senha))
        {
            $_SESSION['user'] = $user->id;
         
            $msg->sucess = true;
            $msg->msg = "Logado com sucesso.";
        } else {
            $msg->msg = "Usuário ou senha inválidos.";
        }

    } catch (Throwable $err) {
        $msg->msg = "Erro interno no servidor. ".$err->getMessage();
        // opcional: $msg->data = ['error' => $err->getMessage()];
    }
}
    
}
catch (Throwable $err) {
    echo  "erro interno ".$err->getMessage();
    exit;
}

// envia a resposta JSON e encerra
$msg->toJson();
