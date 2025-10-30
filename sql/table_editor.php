<?php
// ###########################################
// CORREÇÃO: ADICIONAR ob_start() AQUI
// ###########################################
ob_start(); // Inicia o buffer de saída para evitar o erro "headers already sent"

// OBTIDO DE: table_editor.php (Direcionador Principal)
// Este arquivo foi reescrito para servir como o ponto de entrada modular.

// Inclusão da lógica de autenticação do index.php (Adaptado)
session_start();
$AUTH_USER = 'admin'; 
define('AUTH_COOKIE_NAME', 'sqlite_auth');
$is_logged_in = (isset($_COOKIE[AUTH_COOKIE_NAME]) && $_COOKIE[AUTH_COOKIE_NAME] === hash('sha256', $AUTH_USER . $_SERVER['REMOTE_ADDR']));

if (!$is_logged_in) {
    header('Location: index.php');
    exit;
}
// Fim da Inclusão da lógica de autenticação.

// Configuração dos parâmetros
$database_file = isset($_GET['db']) ? $_GET['db'] : null;
// Correção: Garante que $database_file não é null antes de urlencode
$db_param = urlencode($database_file ?? '');

// Determina qual aba/módulo carregar (padrão: estrutura)
$module = isset($_GET['module']) && in_array($_GET['module'], ['estrutura', 'dados']) ? $_GET['module'] : 'estrutura';

// --- Funções de Ajuda ---

/**
 * Tenta estabelecer a conexão PDO com o arquivo SQLite.
 */
function connect_db($file) {
    if (!file_exists($file)) {
        header("Location: index.php?error=" . urlencode("Arquivo de banco de dados não encontrado: " . $file));
        exit;
    }
    try {
        $pdo = new PDO("sqlite:$file");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        return $pdo;
    } catch (PDOException $e) {
        // Se a conexão falhar, redireciona para o index com erro
        header("Location: index.php?error=" . urlencode("Erro ao conectar ao DB: " . htmlspecialchars($e->getMessage())));
        exit;
    }
}

/**
 * Obtém a lista de tabelas para o menu.
 */
function get_tables_list($pdo) {
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name;");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// --- VALIDAÇÃO E CONEXÃO ---
if (!$database_file) {
    header('Location: index.php');
    exit;
}
$pdo = connect_db($database_file);

// --- LÓGICA DE GET e RENDERIZAÇÃO ---
$tables = get_tables_list($pdo);
// CORREÇÃO ESSENCIAL: Garante que $current_table seja uma string vazia ('') em vez de null se não houver tabelas.
$current_table = isset($_GET['table']) && in_array($_GET['table'], $tables) ? $_GET['table'] : (empty($tables) ? '' : $tables[0]);
$table_param = urlencode($current_table); // AGORA É urlencode('') e não urlencode(null)

// Exibe mensagem de sucesso/erro de redirecionamento (vindo dos submódulos)
$message = null;
if (isset($_GET['message'])) {
    $message = "<div class='alert alert-success' role='alert'><strong>SUCESSO:</strong> " . htmlspecialchars($_GET['message']) . "</div>";
}
if (isset($_GET['error'])) {
    $message = "<div class='alert alert-danger' role='alert'><strong>ERRO:</strong> " . htmlspecialchars($_GET['error']) . "</div>";
}


// -----------------------------------------------------------------
// FUNÇÃO DE CRIAÇÃO DE TABELA (MANTIDA)
// -----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'create_table') {
    $table_name = trim($_POST['table_name'] ?? '');
    $columns = $_POST['columns'] ?? [];
    
    // O $table_param pode estar vazio se for a primeira tabela criada, mas $db_param é sempre válido aqui
    $error_redirect = "Location: table_editor.php?db=$db_param&table=$table_param&module=estrutura&error=";
    // Redireciona para a nova tabela
    $success_redirect = "Location: table_editor.php?db=$db_param&table=" . urlencode($table_name) . "&module=estrutura&message="; 

    if (empty($table_name) || empty($columns)) {
        header($error_redirect . urlencode("O nome da tabela e pelo menos uma coluna são obrigatórios."));
        exit;
    }
    
    $cols_sql = [];
    $has_pk = false;

    try {
        foreach ($columns as $col) {
            $name = trim($col['name']);
            $type = trim($col['type']);
            
            if (empty($name) || empty($type)) {
                throw new Exception("Nome e tipo da coluna não podem ser vazios.");
            }
            
            $sql_def = "\"$name\" $type";
            
            if (isset($col['not_null'])) {
                $sql_def .= " NOT NULL";
            }
            
            if (!empty($col['default'])) {
                $default_val = trim($col['default']);
                $sql_def .= " DEFAULT " . $pdo->quote($default_val);
            }
            
            if (isset($col['pk'])) {
                if ($has_pk) {
                    throw new Exception("Apenas uma coluna pode ser PRIMARY KEY.");
                }
                $sql_def .= " PRIMARY KEY";
                $has_pk = true;
                
                if (isset($col['auto_inc']) && $type === 'INTEGER') {
                    $sql_def .= " AUTOINCREMENT";
                }
            }
            
            $cols_sql[] = $sql_def;
        }

        $sql = "CREATE TABLE IF NOT EXISTS \"$table_name\" (" . implode(', ', $cols_sql) . ")";
        $pdo->exec($sql);
        
        header($success_redirect . urlencode("Tabela '$table_name' criada com sucesso!")); 
        exit;

    } catch (Exception $e) {
        header($error_redirect . urlencode("Erro ao criar tabela: " . $e->getMessage()));
        exit;
    }
}
// -----------------------------------------------------------------
// FIM FUNÇÃO DE CRIAÇÃO DE TABELA
// -----------------------------------------------------------------


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Gerenciar: <?php echo htmlspecialchars(basename($database_file ?? '')); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        .content { padding: 20px; }
        .offcanvas-custom-dark { background-color: #34495e; }
        .offcanvas-custom-dark .list-group-item { background-color: #34495e; border-color: #2c3e50; }
        .offcanvas-custom-dark a { color: #bdc3c7; }
        .offcanvas-custom-dark a:hover { color: #fff; background-color: #2c3e50; }
        .offcanvas-custom-dark a.active { background-color: #3498db; color: #fff; }
    </style>
</head>
<body>
    
    <nav class="navbar navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                <i class="bi bi-list"></i> Menu
            </button>

            <span class="navbar-text ms-auto text-white">
                <a href="index.php" class="text-white text-decoration-none me-3"><i class="bi bi-arrow-left-circle"></i> Index de Bancos</a>
                <span class="text-success fw-bold">Banco: <?php echo htmlspecialchars(basename($database_file ?? '')); ?></span> 
            </span>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 content">
                <h1 class="mb-4">
                    <?php 
                        if ($current_table) { 
                            echo "Tabela: " . htmlspecialchars($current_table);
                        } else { 
                            echo "Gerenciamento de DB";
                        }
                    ?>
                </h1>
                <?php echo $message; ?>

                <?php if ($current_table): ?>
                    <ul class="nav nav-tabs mb-4">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($module === 'estrutura' ? 'active' : ''); ?>" href="?db=<?php echo $db_param; ?>&table=<?php echo $table_param; ?>&module=estrutura"><i class="bi bi-gear"></i> Estrutura</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($module === 'dados' ? 'active' : ''); ?>" href="?db=<?php echo $db_param; ?>&table=<?php echo $table_param; ?>&module=dados"><i class="bi bi-table"></i> Dados (CRUD)</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#executeSqlModal"><i class="bi bi-code-square"></i> Executor SQL (Do original)</a>
                        </li>
                    </ul>
                <?php endif; ?>

                <div id="module-content">
                    <?php
                    // Inclui o módulo dinamicamente
                    if ($current_table) {
                        if ($module === 'estrutura') {
                            include 'estrutura.php'; // CONTÉM: Renomear, Deletar, Adicionar Coluna, Mudar Tipo
                        } elseif ($module === 'dados') {
                            include 'dados.php';     // CONTÉM: CRUD de Linhas
                        } else {
                            include 'estrutura.php';
                        }
                    } else {
                        echo '<div class="alert alert-info">Selecione uma tabela no menu lateral para começar a gerenciar.</div>';
                    }
                    ?>
                </div>

            </div>
        </div>
    </div>
    <div class="offcanvas offcanvas-start offcanvas-custom-dark text-white" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title text-white" id="offcanvasSidebarLabel">Navegação</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <h3 class="text-secondary h6 mt-4">Tabelas</h3>
            <ul class="list-group list-group-flush">
                <?php if (!empty($tables)): ?>
                    <?php foreach ($tables as $table): ?>
                        <li class="list-group-item list-group-item-dark p-0 border-0">
                            <a href="?db=<?php echo $db_param; ?>&table=<?php echo urlencode($table); ?>&module=<?php echo $module; ?>" 
                               class="d-block p-2 <?php echo ($current_table === $table) ? 'active bg-info text-dark' : 'text-info'; ?>">
                                <i class="bi bi-table"></i> <?php echo htmlspecialchars($table); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="list-group-item list-group-item-dark text-muted">Nenhuma tabela.</li>
                <?php endif; ?>
            </ul>
            
            <button type="button" class="btn btn-warning w-100 mt-3 mb-4" data-bs-toggle="modal" data-bs-target="#createTableModal">
                <i class="bi bi-plus-circle"></i> Criar Nova Tabela
            </button>
            
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Seus scripts JS para o modal de criação de tabela e validações...
    </script>
</body>
</html>
<?php 
// ###########################################
// AQUI É O FIM DO ARQUIVO
// ###########################################
ob_end_flush(); // Envia a saída para o navegador no final do script
?>