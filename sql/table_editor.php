<?php
// Configuração
$database_file = isset($_GET['db']) ? $_GET['db'] : null;

// --- Funções de Ajuda ---

/**
 * Tenta estabelecer a conexão PDO com o arquivo SQLite.
 */
function connect_db($file) {
    if (!file_exists($file)) {
        // Redireciona para o index se o arquivo não existir
        header("Location: index.php?error=" . urlencode("Arquivo de banco de dados não encontrado: " . $file));
        exit;
    }
    try {
        $pdo = new PDO("sqlite:$file");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("<h1>❌ Erro de Conexão com SQLite:</h1>" . htmlspecialchars($e->getMessage()));
    }
}

/**
 * Executa uma query SQL e retorna os resultados ou a mensagem de sucesso/erro.
 */
function execute_query($pdo, $sql) {
    try {
        $stmt = $pdo->query($sql);
        if ($stmt) {
            // Verifica se a query é um SELECT
            if ($stmt->columnCount() > 0) {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $columns = $rows ? array_keys($rows[0]) : [];

                return [
                    'type' => 'select',
                    'rows' => $rows,
                    'columns' => $columns,
                    'count' => count($rows)
                ];
            } else {
                // Query de execução (INSERT, UPDATE, DELETE, CREATE, ALTER, DROP)
                return [
                    'type' => 'exec',
                    'message' => "Comando executado com sucesso.",
                    'row_count' => $stmt->rowCount()
                ];
            }
        }
    } catch (PDOException $e) {
        return ['type' => 'error', 'message' => $e->getMessage() . " SQL: " . $sql];
    }
}

// --- Funções de Exportação ---

/**
 * Exporta dados da tabela como CSV.
 */
function export_csv($pdo, $table) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $table . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    
    // Obter dados
    $stmt = $pdo->query("SELECT * FROM \"$table\"");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        fputcsv($output, ['Nenhuma linha encontrada.']);
    } else {
        // Cabeçalho
        fputcsv($output, array_keys($rows[0]));
        // Dados
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
    exit;
}

/**
 * Exporta o esquema da tabela como SQL.
 */
function export_sql($pdo, $table) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $table . '.sql"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Obter o CREATE TABLE
    $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$table'");
    $create_sql = $stmt->fetchColumn();

    if ($create_sql) {
        echo $create_sql . ";\n\n";

        // Obter comandos INSERT
        $stmt = $pdo->query("SELECT * FROM \"$table\"");
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);
        
        $col_names_stmt = $pdo->query("PRAGMA table_info(\"$table\")");
        $col_names = array_column($col_names_stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
        $col_list = implode(', ', array_map(fn($n) => "\"$n\"", $col_names));

        foreach ($rows as $row) {
            $values = implode(', ', array_map(function($v) use ($pdo) {
                // Lida com valores NULL (sem aspas)
                return ($v === null) ? 'NULL' : $pdo->quote($v);
            }, $row));
            echo "INSERT INTO \"$table\" ($col_list) VALUES ($values);\n";
        }
    } else {
        echo "-- Tabela não encontrada.";
    }

    exit;
}

// --- VALIDAÇÃO E CONEXÃO ---
if (!$database_file) {
    header('Location: index.php');
    exit;
}
$pdo = connect_db($database_file);
$db_param = urlencode($database_file);

$output = null; 
$initial_sql = '';
$primary_key_column = 'PK'; // Usamos 'PK' como alias para rowid

// --- Checagem de Exportação (Deve ser antes de qualquer output HTML) ---
if (isset($_GET['action']) && isset($_GET['table'])) {
    $table = $_GET['table'];
    if ($_GET['action'] === 'export_csv') {
        export_csv($pdo, $table);
    } elseif ($_GET['action'] === 'export_sql') {
        export_sql($pdo, $table);
    }
}

// --- LÓGICA DE EDIÇÃO/DELEÇÃO/INSERÇÃO/CRIAÇÃO/DROP/ADD COLUMN (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    
    // EXCLUSÃO DE TABELA (DROP TABLE)
    if ($_POST['action_type'] === 'drop_table' && isset($_POST['table_name'])) {
        $table = $_POST['table_name'];
        $output = execute_query($pdo, "DROP TABLE \"$table\"");
        if ($output['type'] === 'exec') {
            // Redireciona para a página principal após a exclusão da tabela
            header("Location: table_editor.php?db=$db_param&message=" . urlencode("Tabela '$table' excluída com sucesso!"));
            exit;
        }
        
    // ADICIONAR COLUNA (ALTER TABLE ADD COLUMN)
    } elseif ($_POST['action_type'] === 'add_column' && isset($_POST['table_name'], $_POST['new_col_name'], $_POST['new_col_type'])) {
        $table = $_POST['table_name'];
        $col_name = trim($_POST['new_col_name']);
        $col_type = trim($_POST['new_col_type']);
        $col_not_null = isset($_POST['col_not_null']); 
        $col_default = trim($_POST['col_default'] ?? '');

        // --- CORREÇÃO: Lógica para NOT NULL e DEFAULT no SQLite ---
        $col_default_str = '';
        $not_null_str = $col_not_null ? ' NOT NULL' : '';

        if ($col_not_null) {
            // Se NOT NULL é marcado, o valor padrão deve ser fornecido e não pode ser 'NULL'
            if (empty($col_default) || strtoupper($col_default) === 'NULL') {
                $output = ['type' => 'error', 'message' => "Colunas 'NOT NULL' adicionadas a uma tabela existente no SQLite DEVEM ter um valor DEFAULT fornecido (ex: 0, '', 'N/A') que não seja 'NULL'."];
                goto end_of_post_logic; // Salta para o final da lógica POST
            }
            // Se NOT NULL e um valor padrão válido foi fornecido
            $col_default_str = " DEFAULT " . $pdo->quote($col_default);
        } elseif (!empty($col_default) && strtoupper($col_default) !== 'NULL') {
            // Se NÃO for NOT NULL e um valor padrão (diferente de 'NULL') foi fornecido
            $col_default_str = " DEFAULT " . $pdo->quote($col_default);
        }
        // Se NÃO for NOT NULL e DEFAULT estiver vazio/NULL, $col_default_str permanece vazio, o que é o padrão (NULL)

        if (empty($col_name) || empty($col_type)) {
            $output = ['type' => 'error', 'message' => "Nome e Tipo da coluna são obrigatórios."];
        } else {
            $sql = "ALTER TABLE \"$table\" ADD COLUMN \"$col_name\" $col_type $col_default_str $not_null_str";
            $output = execute_query($pdo, $sql);
            if ($output['type'] === 'exec') {
                // Redireciona para exibir a mensagem de sucesso
                header("Location: table_editor.php?db=$db_param&table=" . urlencode($table) . "&message=" . urlencode("Coluna '$col_name' adicionada com sucesso à tabela '$table'."));
                exit;
            }
        }
        
    // CRIAÇÃO DE TABELA
    } elseif ($_POST['action_type'] === 'create_table') {
        $table_name = trim($_POST['new_table_name'] ?? '');
        $columns = $_POST['column'] ?? [];

        if (empty($table_name)) {
            $output = ['type' => 'error', 'message' => "O nome da tabela não pode estar vazio."];
        } elseif (empty($columns)) {
            $output = ['type' => 'error', 'message' => "A tabela deve ter pelo menos uma coluna."];
        } else {
            $column_definitions = [];
            foreach ($columns as $col) {
                // Correção: Garantir que as chaves do array 'col' existam
                $col_name = trim($col['name'] ?? '');
                $col_type = trim($col['type'] ?? '');
                $is_pk = isset($col['pk']);
                
                // SQLite: AUTOINCREMENT só funciona com INTEGER PRIMARY KEY
                $is_autoincrement = $is_pk && ($col_type === 'INTEGER') && isset($col['auto_inc']); 

                $pk_string = '';
                $ai_string = '';
                $nn_string = '';

                if ($is_pk) {
                    $pk_string = 'PRIMARY KEY';
                    if ($is_autoincrement) {
                        $ai_string = 'AUTOINCREMENT';
                    }
                } elseif (isset($col['not_null'])) {
                    $nn_string = 'NOT NULL';
                }

                if (!empty($col_name) && !empty($col_type)) {
                    $definition = "\"$col_name\" $col_type $pk_string $ai_string $nn_string";
                    $column_definitions[] = trim($definition);
                }
            }
            if (empty($column_definitions)) {
                 $output = ['type' => 'error', 'message' => "Nenhuma coluna válida definida."];
            } else {
                $sql = "CREATE TABLE \"$table_name\" (" . implode(', ', $column_definitions) . ")";
                $output = execute_query($pdo, $sql);
                if ($output['type'] === 'exec') {
                    // Redireciona para a visualização da nova tabela
                    header("Location: table_editor.php?db=$db_param&table=" . urlencode($table_name) . "&message=" . urlencode("Tabela '$table_name' criada com sucesso!"));
                    exit;
                }
            }
        }
    } elseif (isset($_POST['table_name'])) {
        // POSTS de Inserção, Atualização e Deleção (CRUD)
        $table = $_POST['table_name'];
        // Garante que a chave primária para CRUD de linha seja sempre 'PK' (o alias para rowid)
        $pk_name_for_where = $_POST['pk_column'] ?? $primary_key_column; 
        $pk_value = $_POST['pk_value'] ?? null;
        
        try {
            if ($_POST['action_type'] === 'delete' && $pk_value !== null) {
                $stmt = $pdo->prepare("DELETE FROM \"$table\" WHERE \"rowid\" = ?"); // SQLite usa rowid para deleção
                $stmt->execute([$pk_value]);
                $output = ['type' => 'exec', 'message' => "Linha excluída com sucesso da tabela '$table'.", 'row_count' => $stmt->rowCount()];
            } elseif ($_POST['action_type'] === 'update' && $pk_value !== null && isset($_POST['column'], $_POST['new_value'])) {
                 $column = $_POST['column'];
                 $new_value = $_POST['new_value'];
                 $stmt = $pdo->prepare("UPDATE \"$table\" SET \"$column\" = ? WHERE \"rowid\" = ?"); // SQLite usa rowid para atualização
                 // Corrigido para garantir que o valor NULL seja passado corretamente ao PDO
                 $value_to_bind = (strtoupper(trim($new_value ?? '')) === 'NULL') ? null : $new_value;
                 $stmt->execute([$value_to_bind, $pk_value]);
                 $output = ['type' => 'exec', 'message' => "Campo '$column' atualizado com sucesso.", 'row_count' => $stmt->rowCount()];
            } elseif ($_POST['action_type'] === 'insert') {
                $insert_data = $_POST['insert_data'] ?? [];
                $columns = []; $placeholders = []; $values = [];
                foreach ($insert_data as $col => $val) {
                    // Se o valor estiver vazio, não incluímos no INSERT, permitindo DEFAULT ou NULL
                    if (trim($val) !== '') {
                        $columns[] = "\"$col\"";
                        $placeholders[] = '?';
                        // Lida com a inserção de strings vazias ou NULL
                        $values[] = (strtoupper(trim($val)) === 'NULL') ? null : $val;
                    }
                }
                if (!empty($columns)) {
                     $sql = "INSERT INTO \"$table\" (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
                     $stmt = $pdo->prepare($sql);
                     $stmt->execute($values);
                     $output = ['type' => 'exec', 'message' => "Nova linha inserida com sucesso na tabela '$table'.", 'row_count' => $stmt->rowCount()];
                } else {
                     // Permite inserção de linha vazia se não houver colunas NOT NULL sem default
                     $sql = "INSERT INTO \"$table\" DEFAULT VALUES";
                     $stmt = $pdo->prepare($sql);
                     $stmt->execute();
                     $output = ['type' => 'exec', 'message' => "Nova linha vazia inserida (se permitido) na tabela '$table'.", 'row_count' => $stmt->rowCount()];
                }
            }
        } catch (PDOException $e) {
             $output = ['type' => 'error', 'message' => $e->getMessage()];
        }
    }
}
// Rótulo para o 'goto'
end_of_post_logic:

// --- LÓGICA DE GET e RENDERIZAÇÃO ---

// Recarrega a lista de tabelas após qualquer ação de alteração de estrutura
$tables_list = execute_query($pdo, "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%';");
$tables = ($tables_list['type'] === 'select') ? array_column($tables_list['rows'], 'name') : [];

// Selecionar tabela atual para visualização
$current_table = isset($_GET['table']) && in_array($_GET['table'], $tables) ? $_GET['table'] : null;

// Obter estrutura da tabela (colunas)
$table_columns = [];
if ($current_table) {
    $stmt = $pdo->query("PRAGMA table_info(\"$current_table\")");
    $table_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


if ($current_table) {
    // Usamos rowid AS PK para garantir que a coluna de chave primária para CRUD seja sempre 'PK'
    $initial_sql = "SELECT rowid AS PK, * FROM \"$current_table\" LIMIT 100;"; 
    $output = execute_query($pdo, $initial_sql);
} elseif ($output === null) {
    // Se houver mensagem de sucesso/erro de um redirecionamento GET
    if (isset($_GET['message'])) {
        $output = ['type' => 'exec', 'message' => htmlspecialchars($_GET['message']), 'row_count' => 0];
    } else {
        $initial_sql = "SELECT 'Bem-vindo ao Gerenciador SQLite!';";
    }
} else {
    // Se a lógica POST foi executada (Executor SQL)
    $initial_sql = $_POST['sql_command'] ?? '';
}

// Se a query POST falhou e era um SELECT, reexecutar o SELECT inicial para mostrar os dados.
if ($output && $output['type'] === 'error' && $current_table) {
    $initial_sql = "SELECT rowid AS PK, * FROM \"$current_table\" LIMIT 100;";
    // Não substitui a mensagem de erro, apenas garante que os dados sejam carregados
    $temp_output = execute_query($pdo, $initial_sql); 
    if ($temp_output['type'] === 'select') {
        $output['rows'] = $temp_output['rows'];
        $output['columns'] = $temp_output['columns'];
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Gerenciar: <?php echo htmlspecialchars($database_file); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        .content { padding: 20px; }
        /* Estilos específicos para a tabela (para visualização de dados) */
        .table-data td { max-width: 150px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        /* Formulários inline de edição */
        .edit-form input { border: none; background: #fff; padding: 5px; width: 100%; box-sizing: border-box; }
        .current-db-link { color: #2ecc71; text-decoration: none; font-weight: bold; }

        /* Estilo customizado para o offcanvas */
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
                <span class="current-db-link">Banco: <?php echo htmlspecialchars($database_file ?? ''); ?></span> 
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
                            echo "Executor SQL";
                        }
                    ?>
                </h1>

                <?php if ($output): ?>
                    <div class="alert <?php echo ($output['type'] === 'error' ? 'alert-danger' : 'alert-success'); ?>" role="alert">
                        <strong><?php echo ($output['type'] === 'error' ? 'ERRO' : 'SUCESSO'); ?>:</strong> <?php echo nl2br(htmlspecialchars($output['message'] ?? '')); ?>
                        <?php if ($output['type'] === 'exec'): ?> (<?php echo $output['row_count']; ?> linhas afetadas) <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($current_table): ?>
                    <div class="btn-group mb-4" role="group">
                        <a href="?db=<?php echo $db_param; ?>&table=<?php echo urlencode($current_table); ?>" class="btn btn-secondary active">Visualizar Dados</a>
                        
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#insertRowModal">
                            <i class="bi bi-plus-lg"></i> Inserir Dados
                        </button>

                        <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#structureModal">
                            Estrutura
                        </button>

                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                Exportar
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?db=<?php echo $db_param; ?>&table=<?php echo urlencode($current_table); ?>&action=export_csv">CSV</a></li>
                                <li><a class="dropdown-item" href="?db=<?php echo $db_param; ?>&table=<?php echo urlencode($current_table); ?>&action=export_sql">SQL</a></li>
                            </ul>
                        </div>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('ATENÇÃO: Deseja realmente DELETAR a tabela <?php echo htmlspecialchars($current_table); ?>?');">
                            <input type="hidden" name="action_type" value="drop_table">
                            <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($current_table); ?>">
                            <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Excluir Tabela</button>
                        </form>
                    </div>

                    <?php if ($output && $output['type'] === 'select'): ?>
                        <h2 class="h4 mb-3">Resultado (<?php echo count($output['rows']); ?> linhas)</h2>
                        <?php if (!empty($output['rows'])): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm table-data">
                                    <thead class="table-dark">
                                        <tr>
                                            <?php foreach ($output['columns'] as $col): ?>
                                                <th><?php echo htmlspecialchars($col); ?></th>
                                            <?php endforeach; ?>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Verifica se a primeira coluna é nossa PK (rowid), permitindo CRUD
                                        $is_editable = (isset($output['rows'][0][$primary_key_column]));
                                        
                                        foreach ($output['rows'] as $row): 
                                            // Pega o valor da PK (rowid)
                                            $pk_value = $row[$primary_key_column] ?? null; 
                                        ?>
                                            <tr>
                                                <?php foreach ($row as $col_name => $value): ?>
                                                    <?php 
                                                    // Tratamento de NULL
                                                    $is_null = $value === null;
                                                    // CORREÇÃO: Usar $value ?? '' para garantir que htmlspecialchars não receba NULL (fixando o erro Deprecated)
                                                    $display_value = $is_null ? '<span class="badge bg-secondary">[NULL]</span>' : htmlspecialchars(substr($value ?? '', 0, 50));
                                                    // O valor completo para o title
                                                    $full_value = $is_null ? '[NULL]' : htmlspecialchars($value ?? '');
                                                    
                                                    if ($is_editable && $col_name !== $primary_key_column): ?>
                                                        <td>
                                                            <form method="POST" class="edit-form" action="table_editor.php?db=<?php echo $db_param; ?>&table=<?php echo urlencode($current_table); ?>">
                                                                <input type="hidden" name="action_type" value="update">
                                                                <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($current_table); ?>">
                                                                <input type="hidden" name="pk_column" value="<?php echo $primary_key_column; ?>">
                                                                <input type="hidden" name="pk_value" value="<?php echo htmlspecialchars($pk_value ?? ''); ?>"> <input type="hidden" name="column" value="<?php echo htmlspecialchars($col_name); ?>">
                                                                <input type="text" name="new_value" value="<?php echo htmlspecialchars($value ?? ''); ?>" class="form-control form-control-sm" placeholder="<?php echo $is_null ? '[NULL]' : ''; ?>" onchange="this.form.submit()">
                                                            </form>
                                                        </td>
                                                    <?php else: ?>
                                                        <td>
                                                            <span title="<?php echo $full_value; ?>">
                                                                <?php echo $display_value; ?>
                                                            </span>
                                                        </td>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                <td>
                                                    <?php if ($is_editable && $pk_value !== null): ?>
                                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja DELETAR esta linha (PK: <?php echo htmlspecialchars($pk_value ?? ''); ?>)?');" action="table_editor.php?db=<?php echo $db_param; ?>&table=<?php echo urlencode($current_table); ?>">
                                                            <input type="hidden" name="action_type" value="delete">
                                                            <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($current_table); ?>">
                                                            <input type="hidden" name="pk_column" value="<?php echo $primary_key_column; ?>">
                                                            <input type="hidden" name="pk_value" value="<?php echo htmlspecialchars($pk_value ?? ''); ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                                        </form>
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="alert alert-info">A consulta foi executada, mas não retornou resultados.</p>
                        <?php endif; ?>
                    <?php endif; ?>

                <?php endif; ?>
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
                            <a href="?db=<?php echo $db_param; ?>&table=<?php echo urlencode($table); ?>" class="d-block p-2 <?php echo ($current_table === $table) ? 'active bg-info text-dark' : 'text-info'; ?>">
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
            
            <h3 class="text-secondary h6 mt-4 border-top pt-3">Executor SQL</h3>
            <form method="POST" class="sql-form">
                <input type="hidden" name="db" value="<?php echo htmlspecialchars($database_file ?? ''); ?>">
                <div class="mb-3">
                    <textarea name="sql_command" class="form-control" placeholder="Digite seu comando SQL aqui..." rows="5"><?php echo htmlspecialchars($initial_sql ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Executar SQL</button>
            </form>
        </div>
    </div>
    <div class="modal fade" id="createTableModal" tabindex="-1" aria-labelledby="createTableModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createTableModalLabel">Criar Nova Tabela</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="table_editor.php?db=<?php echo $db_param; ?>">
                    <div class="modal-body">
                        <input type="hidden" name="action_type" value="create_table">
                        
                        <div class="mb-3">
                            <label for="new_table_name" class="form-label">Nome da Tabela</label>
                            <input type="text" name="new_table_name" id="new_table_name" class="form-control" required>
                        </div>

                        <h6>Definição de Colunas</h6>
                        <div id="column-definitions">
                            </div>
                        <button type="button" id="add-column-btn" class="btn btn-sm btn-info mt-3"><i class="bi bi-plus-lg"></i> Adicionar Coluna</button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Criar Tabela</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php if ($current_table): ?>
    <div class="modal fade" id="insertRowModal" tabindex="-1" aria-labelledby="insertRowModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="insertRowModalLabel">Inserir Nova Linha na Tabela: <?php echo htmlspecialchars($current_table); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="table_editor.php?db=<?php echo $db_param; ?>&table=<?php echo urlencode($current_table); ?>">
                    <div class="modal-body">
                        <input type="hidden" name="action_type" value="insert">
                        <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($current_table); ?>">
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr><th>Coluna</th><th>Valor</th><th>Detalhes</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($table_columns as $col): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($col['name']); ?></td>
                                            <td>
                                                <input type="text" name="insert_data[<?php echo htmlspecialchars($col['name']); ?>]" class="form-control form-control-sm" placeholder="<?php echo ($col['pk']) ? 'PRIMARY KEY' : 'Opcional (Use NULL para valor nulo)'; ?>">
                                            </td>
                                            <td>Tipo: <strong><?php echo htmlspecialchars($col['type']); ?></strong>
                                                <?php echo ($col['notnull']) ? ' | <span class="text-danger">Obrigatório</span>' : ''; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Inserir Dados</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($current_table): ?>
    <div class="modal fade" id="structureModal" tabindex="-1" aria-labelledby="structureModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="structureModalLabel">Estrutura: <?php echo htmlspecialchars($current_table); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning" role="alert">
                        <strong>⚠️ Limitação do SQLite:</strong> A alteração de Tipo de Campo, Auto-Incremento ou remoção de coluna exige reestruturação manual da tabela (DROP/CREATE/COPY DATA). Aqui, apenas a adição de coluna é suportada.
                    </div>

                    <h6 class="mt-3">Colunas Atuais</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Nome</th><th>Tipo</th><th>PK / AI</th><th>NOT NULL</th><th>Valor Padrão</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($table_columns as $col): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($col['name']); ?></td>
                                        <td><?php echo htmlspecialchars($col['type']); ?></td>
                                        <td><?php echo $col['pk'] ? 'PK (Potencialmente AI)' : 'Não'; ?></td>
                                        <td><?php echo $col['notnull'] ? 'Sim' : 'Não'; ?></td>
                                        <td><?php echo htmlspecialchars($col['dflt_value'] ?? 'NULL'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <hr>

                    <h6>Adicionar Nova Coluna (ALTER TABLE ADD COLUMN)</h6>
                    <form method="POST" class="row g-3" action="table_editor.php?db=<?php echo $db_param; ?>&table=<?php echo urlencode($current_table); ?>">
                        <input type="hidden" name="action_type" value="add_column">
                        <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($current_table); ?>">
                        
                        <div class="col-md-3">
                            <label class="form-label">Nome</label>
                            <input type="text" name="new_col_name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo</label>
                            <select name="new_col_type" class="form-select form-select-sm">
                                <option value="TEXT" selected>TEXT</option>
                                <option value="INTEGER">INTEGER</option>
                                <option value="REAL">REAL</option>
                                <option value="BLOB">BLOB</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Default</label>
                            <input type="text" name="col_default" class="form-control form-control-sm" placeholder="Ex: 'valor', 0, NULL">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="col_not_null_check" name="col_not_null">
                                <label class="form-check-label" for="col_not_null_check">NOT NULL</label>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100">Adicionar Coluna</button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        let colCount = 0;

        // Função para adicionar um novo campo de coluna (para o modal Criar Tabela)
        function addColumnField() {
            colCount++;
            const columnHtml = `
                <div class="row g-3 mb-3 border p-2 rounded align-items-end" id="col-row-${colCount}">
                    <div class="col-md-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="column[${colCount}][name]" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipo</label>
                        <select name="column[${colCount}][type]" class="form-select form-select-sm">
                            <option value="TEXT" selected>TEXT</option>
                            <option value="INTEGER">INTEGER</option>
                            <option value="REAL">REAL</option>
                            <option value="BLOB">BLOB</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="column[${colCount}][pk]" id="pk-${colCount}">
                            <label class="form-check-label" for="pk-${colCount}">PRIMARY KEY</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="column[${colCount}][auto_inc]" id="ai-${colCount}" disabled>
                            <label class="form-check-label" for="ai-${colCount}">AUTOINCREMENT</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="column[${colCount}][not_null]" id="nn-${colCount}">
                            <label class="form-check-label" for="nn-${colCount}">NOT NULL</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100 remove-column-btn" data-id="${colCount}">Remover</button>
                    </div>
                </div>
            `;
            $('#column-definitions').append(columnHtml);
        }

        // Adicionar uma coluna inicial ao abrir o modal
        $('#createTableModal').on('show.bs.modal', function () {
            $('#column-definitions').empty(); // Limpa colunas antigas
            colCount = 0;
            addColumnField();
        });

        // Evento de clique para adicionar mais colunas
        $('#add-column-btn').on('click', addColumnField);

        // Evento de clique para remover colunas
        $('#column-definitions').on('click', '.remove-column-btn', function() {
            if ($('#column-definitions').children().length > 1) {
                const id = $(this).data('id');
                $(`#col-row-${id}`).remove();
            } else {
                alert('A tabela deve ter pelo menos uma coluna.');
            }
        });

        // Lógica para desabilitar AUTOINCREMENT se não for INTEGER e PK for desmarcada
        $('#column-definitions').on('change', 'select[name*="[type]"], input[type="checkbox"][name*="[pk]"]', function() {
            const row = $(this).closest('.row');
            const typeSelect = row.find('select[name*="[type]"]');
            const pkCheck = row.find('input[type="checkbox"][name*="[pk]"]');
            const aiCheck = row.find('input[type="checkbox"][name*="[auto_inc]"]');
            const nnCheck = row.find('input[type="checkbox"][name*="[not_null]"]');

            const isInteger = typeSelect.val() === 'INTEGER';
            const isPK = pkCheck.is(':checked');

            if (!isInteger || !isPK) {
                aiCheck.prop('checked', false);
                aiCheck.prop('disabled', true);
            } else {
                aiCheck.prop('disabled', false);
            }
            
            // Força NOT NULL se PRIMARY KEY for marcado (padrão SQL/melhor prática)
            if (isPK) {
                nnCheck.prop('checked', true);
            } 
        });
    });
    </script>
</body>
</html>