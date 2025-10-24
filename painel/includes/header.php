<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<?php

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel</title>

 <!-- Ícones -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
 <!-- jquery -->
 <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
 <!-- bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
 <!-- bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


 <!-- chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script src="//cdn.jsdelivr.net/npm/eruda"></script>

    <style>
        body {
            background-color: #f5f6fa;
        }
        .sidebar {
            width: 240px;
            background: #343a40;
            color: #fff;
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 60px;
        }
        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
        }
        .sidebar a:hover, .sidebar a.active {
            background: #495057;
            color: #fff;
        }
        .content {
            margin-left: 240px;
            padding: 20px;
        }
        .navbar {
            background-color: #212529;
            color: #fff;
        }
    </style>
</head>


<body>
    
    
<nav class="navbar fixed-top">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1 text-light">🎮 Painel Administrativo</span>
        <div>
                <a href="index.php" class="active">
        <span class="material-symbols-outlined">dashboard</span> Dashboard
    </a>
    <a href="games.php">
        <span class="material-symbols-outlined">sports_esports</span> Jogos
    </a>
    <a href="historico.php">
        <span class="material-symbols-outlined">history</span> Histórico
    </a>
    <a href="carteiras.php">
        <span class="material-symbols-outlined">group</span> Carteira
    </a>
    <a href="configuracoes.php">
        <span class="material-symbols-outlined">settings</span> Configurações
    </a>
            <a href="perfil.php" class="btn btn-sm btn-outline-light">Perfil</a>
            <a href="/app/api/user/logout.php" class="btn btn-sm btn-danger">Sair</a>
        </div>
    </div>
</nav>
