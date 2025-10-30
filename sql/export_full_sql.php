<?php
// Configuração
$database_file = isset($_GET['db']) ? $_GET['db'] : null;

if (!$database_file || !file_exists($database_file)) {
    header('Location: index.php?error=' . urlencode('Arquivo de banco de dados SQLite não encontrado ou nome inválido.'));
    exit;
}

/**
 * Tenta estabelecer a conexão PDO com o arquivo SQLite.
 */
function connect_db($file) {
    try {
        $pdo = new PDO("sqlite:$file");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("<h1>❌ Erro de Conexão com SQLite:</h1>" . htmlspecialchars($e->getMessage()));
    }
}

/**
 * Mapeamento básico de tipos de dados SQLite para MySQL (usado para reconstruir o CREATE TABLE).
 */
function map_sqlite_type_to_mysql($sqlite_type) {
    $type = strtoupper(trim($sqlite_type));
    
    // SQLite: INTEGER, REAL, TEXT, BLOB, NULL
    if (strpos($type, 'INT') !== false) return 'INT';
    if (strpos($type, 'CHAR') !== false || strpos($type, 'CLOB') !== false) return 'TEXT';
    if (strpos($type, 'REAL') !== false || strpos($type, 'FLOA') !== false) return 'FLOAT';
    if (strpos($type, 'BLOB') !== false) return 'LONGBLOB';

    // Padrões
    if ($type === 'TEXT') return 'LONGTEXT';
    if ($type === 'INTEGER') return 'INT';
    if ($type === 'REAL') return 'FLOAT';
    if ($type === 'BLOB') return 'LONGBLOB';
    
    return 'VARCHAR(255)'; // Tipo default seguro
}

// Conexão
$pdo = connect_db($database_file);

// --- Capturar Parâmetros de Exportação ---
$export_type = $_GET['export_type'] ?? 'both'; 

// --- 1. Configurar Cabeçalhos para Download ---
$filename = basename($database_file, '.sqlite') . '_dump_' . date('Ymd_His') . '.sql';

header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// --- 2. Gerar Cabeçalho SQL ---
fwrite($output, "--\n");
fwrite($output, "-- Dump do Banco de Dados SQLite: " . $database_file . "\n");
fwrite($output, "-- Gerado em: " . date('Y-m-d H:i:s') . "\n");
fwrite($output, "-- Tipo de Exportação: " . $export_type . "\n");
fwrite($output, "--\n\n");

// Comandos de compatibilidade MySQL
fwrite($output, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
fwrite($output, "SET time_zone = \"+00:00\";\n\n");

// --- 3. Obter todas as tabelas ---
$stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%';");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($tables)) {
    fwrite($output, "-- Nenhuma tabela encontrada no banco de dados.\n");
    fclose($output);
    exit;
}

foreach ($tables as $table) {
    fwrite($output, "\n-- --------------------------------------------------------\n");
    fwrite($output, "-- Tabela: `$table`\n");
    fwrite($output, "--\n");

    // A. DROP TABLE (Se for estrutura ou ambos)
    if ($export_type === 'both' || $export_type === 'structure') {
        fwrite($output, "DROP TABLE IF EXISTS `$table`;\n");
    }
    
    // B. Gerar CREATE TABLE (Se for estrutura ou ambos)
    if ($export_type === 'both' || $export_type === 'structure') {
        
        // 1. Obter estrutura da tabela (colunas)
        $stmt_cols = $pdo->query("PRAGMA table_info(\"$table\")");
        $columns_info = $stmt_cols->fetchAll(PDO::FETCH_ASSOC);

        $column_defs = [];
        $primary_keys = [];
        $has_autoincrement = false;

        foreach ($columns_info as $col) {
            $col_name = $col['name'];
            $sqlite_type = $col['type'];
            $mysql_type = map_sqlite_type_to_mysql($sqlite_type);
            $not_null = $col['notnull'] ? 'NOT NULL' : 'NULL';
            $default = $col['dflt_value'] !== null ? "DEFAULT " . $pdo->quote($col['dflt_value']) : '';
            $auto_inc = '';

            // Lógica AUTO_INCREMENT: Se for PK e INTEGER, definimos AUTO_INCREMENT
            if ($col['pk'] && $mysql_type === 'INT') {
                $auto_inc = 'AUTO_INCREMENT'; 
                $has_autoincrement = true;
            }
            
            $column_defs[] = "`$col_name` $mysql_type $not_null $default $auto_inc";
            if ($col['pk']) {
                $primary_keys[] = "`$col_name`";
            }
        }
        
        if (!empty($primary_keys)) {
            $column_defs[] = "PRIMARY KEY (" . implode(', ', $primary_keys) . ")";
        }

        $create_table_sql = "CREATE TABLE `$table` (" . implode(', ', $column_defs) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n";
        fwrite($output, $create_table_sql);
        
        // Se a tabela tiver AUTO_INCREMENT, adicionamos o comando ALTER TABLE para setar o AUTO_INCREMENT inicial
        if ($has_autoincrement) {
            $stmt_seq = $pdo->query("SELECT seq FROM sqlite_sequence WHERE name='$table'");
            $current_seq = $stmt_seq->fetchColumn() ?? 0;
            fwrite($output, "ALTER TABLE `$table` AUTO_INCREMENT = " . ($current_seq + 1) . ";\n");
        }
        fwrite($output, "\n");
    }


    // C. Obter e gerar todos os comandos INSERT (Se for dados ou ambos)
    if ($export_type === 'both' || $export_type === 'data') {
        fwrite($output, "-- Inserindo dados na tabela: `$table`\n");
        
        $stmt_data = $pdo->query("SELECT * FROM \"$table\"");
        $data_rows = $stmt_data->fetchAll(PDO::FETCH_NUM);
        
        if (!empty($data_rows)) {
            // Obter os nomes das colunas para o comando INSERT
            $col_names_stmt = $pdo->query("PRAGMA table_info(\"$table\")");
            $col_names = array_column($col_names_stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
            $col_list = implode(', ', array_map(fn($n) => "`$n`", $col_names));

            foreach ($data_rows as $row) {
                $values = implode(', ', array_map(function($v) use ($pdo) {
                    // Se o valor for NULL, não coloca aspas. Senão, usa $pdo->quote() para escapar
                    return ($v === null) ? 'NULL' : $pdo->quote($v);
                }, $row));
                
                // Gerar o comando INSERT completo
                fwrite($output, "INSERT INTO `$table` ($col_list) VALUES ($values);\n");
            }
        } else {
            fwrite($output, "-- Tabela `$table` está vazia.\n");
        }
        fwrite($output, "\n");
    }
}

// Finalizar e fechar o arquivo
fwrite($output, "\n-- Dump concluído.\n");
fclose($output);
exit;