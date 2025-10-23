<?php
$repoUrl = "https://github.com/Adrianogyn1/Websiteteste/archive/refs/heads/Main.zip";
$zipFile = __DIR__ . "/repositorio.zip";
$extractTo = __DIR__ . "/";
$msg = "";
$ultimaAtualizacao = null;

// Verifica data/hora da última atualização
if (file_exists($extractTo)) {
    $ultimaAtualizacao = date("d/m/Y H:i:s", filemtime($extractTo));
}

if (isset($_POST['atualizar'])) {
    // Baixa o ZIP
    file_put_contents($zipFile, file_get_contents($repoUrl));

    // Remove pasta antiga se existir
    if (is_dir($extractTo)) {
        $it = new RecursiveDirectoryIterator($extractTo, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file) : unlink($file);
        }
        rmdir($extractTo);
    }

    // Extrai ZIP
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        $zip->extractTo($extractTo);
        $zip->close();
        $msg = "✅ Repositório atualizado com sucesso!";
        $ultimaAtualizacao = date("d/m/Y H:i:s");
    } else {
        $msg = "❌ Falha ao extrair o ZIP";
    }
}

// Função para listar arquivos da pasta
function listarArquivos($dir, $prefixo = "") {
    $arquivos = scandir($dir);
    echo "<ul>";
    foreach ($arquivos as $arq) {
        if ($arq === "." || $arq === "..") continue;
        $caminho = $dir . "/" . $arq;
        if (is_dir($caminho)) {
            echo "<li><strong>$prefixo$arq/</strong>";
            listarArquivos($caminho, $prefixo . "  ");
            echo "</li>";
        } else {
            echo "<li>$prefixo$arq</li>";
        }
    }
    echo "</ul>";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Atualizar Repositório</title>
<style>
body { font-family: sans-serif; padding: 2rem; background: #f8f9fa; }
button { padding: .5rem 1rem; font-size: 1rem; }
.container { max-width: 800px; margin: auto; background: #fff; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);}
ul { list-style-type: disc; margin-left: 20px; }
</style>
</head>
<body>
<div class="container">
<h2>Atualizar Repositório do GitHub</h2>

<form method="post">
    <button name="atualizar" type="submit">🔄 Atualizar Repositório</button>
</form>



<p><?= htmlspecialchars($msg) ?></p>

<?php if ($ultimaAtualizacao): ?>
<p>🕒 Última atualização: <?= $ultimaAtualizacao ?></p>
<?php endif; ?>

<?php if (is_dir($extractTo)): ?>
<h3>📂 Conteúdo da pasta extraída:</h3>
<?php listarArquivos($extractTo); ?>
<?php endif; ?>

</div>
</body>
</html>
