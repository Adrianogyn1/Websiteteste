<?php include __DIR__.'/includes/header.php'; ?>

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
    display: none;
    margin-top: 8px;
}
</style>
</head>
<body>

<div class="card p-4">
    <h4 class="text-center mb-3">Criar Conta</h4>

    <input type="text" id="nome" class="form-control mb-2" placeholder="Nome">
    <input type="email" id="email" class="form-control mb-2" placeholder="Email">
    <input type="password" id="senha" class="form-control mb-2" placeholder="Senha">

    <button id="btnCadastrar" class="btn btn-primary w-100">Cadastrar</button>

    <div class="progress mt-3">
        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%"></div>
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
        const bar = $('.progress-bar');

        msg.text('').removeClass('text-success text-danger');

        if (!nome || !email || !senha) {
            msg.text('Preencha todos os campos').addClass('text-danger');
            return;
        }

        // mostra progress
        progress.show();
        bar.css('width', '50%');

        $.ajax({
            url: '/app/api/user/cadastro.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ nome, email, senha }),
            success: function(resp) {
                bar.css('width', '100%');
                if (resp.sucess) {
                    msg.removeClass('text-danger').addClass('text-success').text(resp.msg);
                    setTimeout(() => window.location.href = 'login.php', 800);
                } else {
                    msg.removeClass('text-success').addClass('text-danger').text(resp.msg || 'Erro ao cadastrar');
                }
            },
            error: function() {
                bar.css('width', '100%').removeClass('bg-success').addClass('bg-danger');
                msg.removeClass('text-success').addClass('text-danger').text('Erro ao conectar com o servidor!');
            },
            complete: function() {
                setTimeout(() => progress.fadeOut(500), 1000);
            }
        });
    });
});
</script>


        <?php include __DIR__.'/includes/footer.php'; ?>

