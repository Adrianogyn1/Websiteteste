<?php
require_once(dirname(__DIR__, 2) . '/autoload.php');
session_start();

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

        $stmt = $db->query("SELECT * FROM users WHERE email = ?", [$email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            $msg->msg = "Usuário não encontrado.";
            $msg->toJson();
        }

        $user = User::createFromArray($data);

        if (password_verify($senha, $user->senha)) {
            $_SESSION['user'] = $user->email;
            $msg->sucess = true;
            $msg->msg = "Logado com sucesso.";
            $msg->data = ['user' => $user->email]; // opcional: retorna dados do usuário
        } else {
            $msg->msg = "Usuário ou senha inválidos.";
        }

    } catch (Throwable $err) {
        $msg->msg = "Erro interno no servidor.";
        // opcional: $msg->data = ['error' => $err->getMessage()];
    }
}

// envia a resposta JSON e encerra
$msg->toJson();
