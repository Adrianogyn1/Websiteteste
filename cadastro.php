<?php
require_once __DIR__ . '/models/UserPdo.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
   try {
    $json = json_decode(file_get_contents('php://input'), true);
    $nome = $json['nome'] ?? '';
    $email = $json['email'] ?? '';
    $senha = $json['senha'] ?? '';

    if (!$nome || !$email || !$senha) {
        http_response_code(400);
        echo json_encode(['erro' => 'Preencha todos os campos']);
        exit;
    }

    $user = new User($nome, $email, $senha);
    $db = new UserPdo();

    
        $db->save($user);
        echo json_encode(['ok' => true]);
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(['erro' => 'Erro ao cadastrar']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Cadastro</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<style>
body {
    background: #f8f9fa;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}
.card {
    width: 360px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-radius: 12px;
}
.progress {
    height: 6px;
    margin-top: 8px;
    display: none;
}
</style>
</head>
<body>

<div class="card p-4">
    <h4 class="text-center mb-3">Criar Conta</h4>

    <div class="mb-3">
        <input type="text" id="nome" class="form-control" placeholder="Nome">
    </div>
    <div class="mb-3">
        <input type="email" id="email" class="form-control" placeholder="Email">
    </div>
    <div class="mb-3">
        <input type="password" id="senha" class="form-control" placeholder="Senha">
    </div>

    <button id="btnCadastrar" class="btn btn-primary w-100">Cadastrar</button>

    <div class="progress mt-3">
        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%;"></div>
    </div>

    <div id="msg" class="text-center mt-3"></div>

    <div class="text-center mt-3">
        <a href="login.php">Já tem conta? Entrar</a>
    </div>
</div>

<script>
$(function() {
    $('#btnCadastrar').on('click', function() {
        const nome = $('#nome').val().trim();
        const email = $('#email').val().trim();
        const senha = $('#senha').val().trim();
        const msg = $('#msg');
        const progress = $('.progress');
        const bar = $('#progressBar');

        msg.text('');
        if (!nome || !email || !senha) {
            msg.text('Preencha todos os campos').addClass('text-danger');
            return;
        }

        progress.show();
        bar.css('width', '0%');

        // Simula carregamento
        let p = 0;
        const timer = setInterval(() => {
            p += 10;
            bar.css('width', p + '%');
            if (p >= 90) clearInterval(timer);
        }, 100);

        $.ajax({
            url: 'cadastro.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ nome, email, senha }),
            success: function(resp) {
                clearInterval(timer);
                bar.css('width', '100%');
                msg.removeClass('text-danger').addClass('text-success').text('Cadastro realizado com sucesso!');
                setTimeout(() => window.location.href = 'login.php', 800);
            },
            error: function(xhr) {
                clearInterval(timer);
                bar.css('width', '100%').removeClass('bg-success').addClass('bg-danger');
                const erro = xhr.responseJSON?.erro || 'Erro ao cadastrar';
                msg.removeClass('text-success').addClass('text-danger').text(erro);
            },
            complete: function() {
                setTimeout(() => progress.fadeOut(500), 1000);
            }
        });
    });
});
</script>

</body>
</html>
