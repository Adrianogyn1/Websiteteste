<?php
require_once __DIR__ . '/htdocs/app/models/UserPdo.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = json_decode(file_get_contents('php://input'), true);
    $email = $json['email'] ?? '';
    $senha = $json['senha'] ?? '';

    $db = new UserPdo();
    $user = $db->login($email, $senha);

    if ($user) {
        $_SESSION['user'] = $user->email;
        echo json_encode(['ok' => true]);
    } else {
        http_response_code(401);
        echo json_encode(['erro' => 'Credenciais inválidas']);
    }
    exit;
}
?>