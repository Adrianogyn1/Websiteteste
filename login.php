<?php include __DIR__.'/includes/header.php'; ?>

<style>
body {
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
}
.card {
    width: 100%;
    max-width: 400px;
    border-radius: 1rem;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
</style>
</head>

<body>
<div class="card p-4">
    <h3 class="text-center mb-4">Login</h3>

    <div class="mb-3">
        <label for="email" class="form-label">E-mail</label>
        <input type="email" id="email" class="form-control" placeholder="Digite seu e-mail">
    </div>

    <div class="mb-3">
        <label for="senha" class="form-label">Senha</label>
        <input type="password" id="senha" class="form-control" placeholder="Digite sua senha">
    </div>

    <button id="btnLogin" class="btn btn-primary w-100">Entrar</button>

    <div id="msg" class="mt-3 text-center"></div>
</div>





<script>
$(document).ready(function() {
    $("#btnLogin").click(function() {
        const email = $("#email").val().trim();
        const senha = $("#senha").val().trim();
        const msg = $("#msg");

        msg.removeClass().text("");

        if (!email || !senha) {
            msg.addClass("text-danger").text("Preencha todos os campos!");
            return;
        }

        msg.addClass("text-secondary").text("Verificando...");

        $.ajax({
            url: "/app/api/user/login.php",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({ email, senha }),
            success: function(data) {
                // Ajustado para ApiMessage
                if (data.sucess) {
                    msg.removeClass().addClass("text-success").text(data.msg || "Entrando...");
                    setTimeout(() => window.location.href = "/app/painel", 500);
                } else {
                    msg.removeClass().addClass("text-danger").text(data.msg || "Usuário ou senha inválidos!");
                }
            },
            error: function() {
                msg.removeClass().addClass("text-danger").text("Erro ao conectar com o servidor!");
            }
        });
    });
});
</script>

        <?php include __DIR__.'/includes/footer.php'; ?>

