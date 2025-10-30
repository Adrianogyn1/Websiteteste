<?php
header('Content-Type: text/html; charset=utf-8');

$database_file = isset($_GET['db']) ? $_GET['db'] : null;

if (!$database_file || !file_exists($database_file)) {
    echo "<p class='text-danger'>Arquivo de banco de dados não encontrado.</p>";
    exit;
}

try {
    $pdo = new PDO("sqlite:$database_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%';");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p class='alert alert-warning mb-0'>Nenhuma tabela encontrada neste banco de dados.</p>";
        exit;
    }

    echo "<div class='border p-3 rounded bg-light'>";
    echo "<label class='form-label fw-bold d-block mb-2'>Tabelas a Exportar:</label>";
    echo "<div class='form-check'>";
    echo "<input class='form-check-input' type='checkbox' id='select-all-tables'>";
    echo "<label class='form-check-label fw-bold' for='select-all-tables'>Selecionar Todas</label>";
    echo "</div><hr class='my-2'>";
    
    foreach ($tables as $table) {
        echo "<div class='form-check'>";
        echo "<input class='form-check-input table-checkbox' type='checkbox' name='tables[]' value='" . htmlspecialchars($table) . "' id='table-" . htmlspecialchars($table) . "' checked>";
        echo "<label class='form-check-label' for='table-" . htmlspecialchars($table) . "'>" . htmlspecialchars($table) . "</label>";
        echo "</div>";
    }
    echo "</div>";

} catch (PDOException $e) {
    echo "<p class='alert alert-danger mb-0'>Erro ao conectar ao banco de dados: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?>