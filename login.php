


<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Login</title>
<style>
body { font-family: sans-serif; padding: 2rem; }
input, button { display: block; margin: .5rem 0; padding: .5rem; width: 250px; }
</style>
</head>
<body>
<h2>Login</h2>

<input type="email" id="email" placeholder="Email">
<input type="password" id="senha" placeholder="Senha">
<button onclick="login()">Entrar</button>

<p id="msg"></p>

<script>
async function login() {
    const email = document.getElementById('email').value;
    const senha = document.getElementById('senha').value;

    const res = await fetch('login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, senha })
    });

    const data = await res.json();
    const msg = document.getElementById('msg');
    msg.textContent = data.ok ? 'Entrando...' : data.erro;
    if (data.ok) setTimeout(() => location.href = 'painel.php', 1000);
}
</script>
</body>
</html>
