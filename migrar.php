<?php
$mysqlHost = 'sql105.infinityfree.com';
$mysqlDb   = 'if0_39810583_website';
$mysqlUser = 'if0_39810583';
$mysqlPass = 'comsenha12';

// Arquivo SQLite
$sqliteFile = __DIR__ . '/GestaoDatabase.db3';

try {
    // Conexão SQLite
    $sqlite = new PDO("sqlite:$sqliteFile");
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Conexão MySQL
    $mysqlDsn = "mysql:host=$mysqlHost;dbname=$mysqlDb;charset=utf8mb4";
    $mysql = new PDO($mysqlDsn, $mysqlUser, $mysqlPass);
    $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buscar tabelas SQLite (ignorando internas)
    $tables = $sqlite->query("
        SELECT name 
        FROM sqlite_master 
        WHERE type='table' 
          AND name NOT LIKE 'sqlite_%'
          AND name != 'android_metadata';
    ")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        echo "Migrando tabela: $table\n";

        // Pega CREATE TABLE SQLite
        $createStmt = $sqlite->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$table'")
                             ->fetch(PDO::FETCH_COLUMN);

        // Ajustes para MySQL
        $createStmt = str_ireplace('AUTOINCREMENT', 'AUTO_INCREMENT', $createStmt);
        $createStmt = preg_replace('/\bTEXT\b/i', 'VARCHAR(255)', $createStmt);
        $createStmt = preg_replace('/\bVARCHAR\b/i', 'VARCHAR(255)', $createStmt);
        $createStmt = preg_replace('/\bINTEGER\b/i', 'INT', $createStmt);
        $createStmt = preg_replace('/\bREAL\b/i', 'FLOAT', $createStmt);
        $createStmt = preg_replace('/\bNUMERIC\b/i', 'DECIMAL(10,2)', $createStmt);
        $createStmt = preg_replace('/\bBOOLEAN\b/i', 'TINYINT(1)', $createStmt);
        $createStmt = preg_replace('/CREATE TABLE/i', 'CREATE TABLE IF NOT EXISTS', $createStmt);

        try {
            // Cria tabela no MySQL
            $mysql->exec($createStmt);

            // Copiar dados
            $rows = $sqlite->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
            if ($rows) {
                $columns = array_keys($rows[0]);
                $colList = implode(',', array_map(fn($c) => "`$c`", $columns));
                $placeholders = implode(',', array_fill(0, count($columns), '?'));
                $stmt = $mysql->prepare("INSERT INTO `$table` ($colList) VALUES ($placeholders)");

                foreach ($rows as $row) {
                    // Converte boolean para int
                    foreach ($row as $k => $v) {
                        if (is_bool($v)) $row[$k] = $v ? 1 : 0;
                    }
                    $stmt->execute(array_values($row));
                }
            }

            echo "Tabela $table migrada com " . count($rows) . " registros.\n";

        } catch (PDOException $e) {
            echo "Erro migrando $table: " . $e->getMessage() . "\n";
        }
    }

    echo "Migração concluída!\n";

} catch (PDOException $e) {
    echo "Erro de conexão: " . $e->getMessage();
}
