<?php
// index.php da API
echo "<h1>API</h1>";

// Pasta atual
$dir = __DIR__;

// Lista de arquivos e pastas
$items = scandir($dir);

// Filtra "." e ".."
$items = array_filter($items, fn($item) => !in_array($item, ['.', '..']));

echo "<ul>";
foreach ($items as $item) {
    $path = $dir . DIRECTORY_SEPARATOR . $item;

    if (is_dir($path)) {
        // Link para a pasta
        echo "<li><strong>📁 <a href='$item/'>$item/</a></strong></li>";
    } else {
        // Link para arquivo
        echo "<li>📄 <a href='$item'>$item</a></li>";
    }
}
echo "</ul>";
?>
