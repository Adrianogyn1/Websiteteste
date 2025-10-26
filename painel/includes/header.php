<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Date Range Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <!-- Eruda -->
    <script src="//cdn.jsdelivr.net/npm/eruda"></script>

    <style>
        body {
            background-color: #f5f6fa;
            overflow-x: hidden;
        }

        /* ===== NAVBAR SUPERIOR ===== */
        .navbar {
            background-color: #212529;
        }
        .navbar-brand {
            color: #fff !important;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        .navbar .btn {
            font-size: 0.9rem;
        }

        /* ===== MENU LATERAL (OFFCANVAS) ===== */
        .offcanvas {
            background-color: #343a40;
            color: #fff;
        }
        .offcanvas-header {
            border-bottom: 1px solid #495057;
        }
        .offcanvas a {
            color: #adb5bd;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 5px;
            transition: all 0.2s ease;
        }
        .offcanvas a:hover, .offcanvas a.active {
            background: #495057;
            color: #fff;
        }
        .offcanvas .material-symbols-outlined {
            font-size: 22px;
        }

        /* ===== LAYOUT DESKTOP ===== */
        @media (min-width: 992px) {
            .offcanvas-lg {
                position: static;
                transform: none !important;
                visibility: visible !important;
                width: 240px;
                height: 100vh;
            }
            main {
                margin-left: 240px;
            }
        }

        main {
            padding-top: 70px;
        }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-thumb {
            background-color: #adb5bd;
            border-radius: 4px;
        }
    </style>
</head>

<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-dark fixed-top shadow-sm">
    <div class="container-fluid">
        <!-- Botão para abrir menu lateral (mobile) -->
        <button class="btn btn-outline-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuLateral">
            <span class="material-symbols-outlined">menu</span>
        </button>

        <!-- Logo -->
        <a class="navbar-brand" href="#">
            <span class="material-symbols-outlined">stadia_controller</span>
            Painel
        </a>

        <!-- Botões da direita -->
        <div class="d-flex align-items-center gap-2">
            <a href="perfil.php" class="btn btn-sm btn-outline-light">Perfil</a>
            <a href="/app/api/user/logout.php" class="btn btn-sm btn-danger">Sair</a>
        </div>
    </div>
</nav>

<!-- ===== MENU LATERAL ===== -->
<div class="offcanvas offcanvas-start offcanvas-lg" tabindex="-1" id="menuLateral">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Menu</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
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
            <span class="material-symbols-outlined">account_balance_wallet</span> Carteiras
        </a>
        <a href="gestao.php">
            <span class="material-symbols-outlined">monitoring</span> Gestão
        </a>
         <a href="../chat/index.php">
            <span class="material-symbols-outlined">settings</span> chat
        </a>
        
        <a href="configuracoes.php">
            <span class="material-symbols-outlined">settings</span> Configurações
        </a>
    </div>
</div>
