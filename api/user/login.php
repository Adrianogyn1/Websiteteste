<?php
require_once(dirname(__DIR__, 3) . '/autoload.php');
session_start();

header('Content-Type: application/json; charset=utf-8');

// Instância inicial da resposta
$msg = new ApiResposta();
$msg->sucess = false;
$msg->msg = "Credenciais inválidas.";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $json = json_decode(file_get_contents('php://input'), true);
    $email = trim($json['email'] ?? '');
    $senha = trim($json['senha'] ?? '');

    if (empty($email) || empty($senha)) {
        $msg->msg = "Preencha todos os campos.";
        echo json_encode($msg);
        exit;
    }

    try {
        $db = new Database();

        $stmt = $db->query("SELECT * FROM users WHERE email = ?", [$email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            $msg->msg = "Usuário não encontrado.";
            echo $msg;
            exit;
        }

        $user = User::createFromArray($data);

        if (password_verify($senha, $user->senha)) {
            $_SESSION['user'] = $user->email;
            $msg->sucess = true;
            $msg->msg = "Logado com sucesso.";
        } else {
            $msg->msg = "Usuário ou senha inválidos.";
        }

    } catch (Throwable $err) {
        $msg->msg = "Erro interno no servidor.";
        $msg->erro = $err->getMessage(); // opcional, para debug interno
    }
}

echo $msg;
