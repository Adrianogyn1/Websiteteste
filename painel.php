<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Painel</title>
</head>
<body>
    <h2>Bem-vindo, <?= htmlspecialchars($_SESSION['user']) ?></h2>
    <a href="logout.php">Sair</a>
</body>
</html>
