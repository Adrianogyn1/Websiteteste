<?php
// Config MySQL
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
    $mysql = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDb;charset=utf8mb4", $mysqlUser, $mysqlPass);
    $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Pegar todas as tabelas
   // $tables = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table';")->fetchAll(PDO::FETCH_COLUMN);
// Pegar todas as tabelas, exceto internas
$tables = $sqlite->query("
    SELECT name 
    FROM sqlite_master 
    WHERE type='table' 
    AND name NOT LIKE 'sqlite_%';
")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        echo "Migrando tabela: $table\n";

        // Pega CREATE TABLE do SQLite
        $create = $sqlite->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$table';")->fetchColumn();

        // Ajustes básicos para MySQL
        $create = str_ireplace('AUTOINCREMENT', 'AUTO_INCREMENT', $create);
        $create = preg_replace('/"(.*?)"/', '`$1`', $create); // aspas duplas para crases
        $create = preg_replace('/INTEGER PRIMARY KEY/', 'INT PRIMARY KEY AUTO_INCREMENT', $create);
        $create = preg_replace('/TEXT/', 'VARCHAR(255)', $create);
        $create = preg_replace('/REAL/', 'FLOAT', $create);
        $create = preg_replace('/BOOLEAN/', 'TINYINT(1)', $create);

        // Cria tabela MySQL
        $mysql->exec("DROP TABLE IF EXISTS `$table`;");
        $mysql->exec($create);

        // Pega dados
        $rows = $sqlite->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) continue;

        $columns = array_keys($rows[0]);
        $colsStr = '`' . implode('`,`', $columns) . '`';
        $placeholders = rtrim(str_repeat('?,', count($columns)), ',');

        $stmt = $mysql->prepare("INSERT INTO `$table` ($colsStr) VALUES ($placeholders)");

        foreach ($rows as $row) {
            $stmt->execute(array_values($row));
        }

        echo "Tabela $table migrada com " . count($rows) . " registros.\n";
    }

    echo "Migração concluída!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
