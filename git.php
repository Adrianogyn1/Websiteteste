<?php
$usuario = "Adrianogyn1";      // GitHub username
$repositorio = "Websiteteste"; // repo
$branch = "main";

$tmpDir = __DIR__ . "/tmp_update";
$destDir = __DIR__ . "/app";
$zipFile = __DIR__ . "/tmp_repo.zip";
$msg = "";

// Obter último commit
$ultimoCommit = getUltimoCommit($usuario, $repositorio, $branch);
$ultimoCommitData = $ultimoCommit ? date("d/m/Y H:i:s", strtotime($ultimoCommit)) : null;

// Atualizar repositório
if (isset($_POST['atualizar'])) {
    $zipUrl = "https://github.com/$usuario/$repositorio/archive/refs/heads/$branch.zip";

    // Baixar ZIP
    file_put_contents($zipFile, file_get_contents($zipUrl));

    // Limpar pasta temporária
    if (is_dir($tmpDir)) {
        $it = new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) $file->isDir() ? rmdir($file) : unlink($file);
        rmdir($tmpDir);
    }
    mkdir($tmpDir);

    // Extrair ZIP
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        $zip->extractTo($tmpDir);
        $zip->close();
        $msg = "✅ ZIP extraído na pasta temporária.";

        // Copiar arquivos para a pasta principal
        $extractedFolder = glob("$tmpDir/*")[0]; // primeira pasta dentro do ZIP
        recursiveCopy($extractedFolder, $destDir);
        $msg .= " ✅ Arquivos movidos para $destDir";
    } else {
        $msg = "❌ Falha ao extrair ZIP";
    }
}

// Função para copiar recursivamente, ignorando o próprio script
function recursiveCopy($src, $dst) {
    $dir = opendir($src);
    if (!is_dir($dst)) mkdir($dst, 0755, true);
    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') continue;
        $srcPath = "$src/$file";
        $dstPath = "$dst/$file";

        // Ignorar o script de atualização
        if (basename(__FILE__) === $file && dirname(__FILE__) === $dst) continue;

        if (is_dir($srcPath)) {
            recursiveCopy($srcPath, $dstPath);
        } else {
            copy($srcPath, $dstPath);
        }
    }
    closedir($dir);
}

// Função para listar arquivos
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

// Função para obter último commit
function getUltimoCommit($usuario, $repo, $branch) {
    $url = "https://api.github.com/repos/$usuario/$repo/commits/$branch";
    $opts = ["http" => ["header" => "User-Agent: PHP\r\n"]];
    $context = stream_context_create($opts);
    $json = file_get_contents($url, false, $context);
    if (!$json) return null;
    $data = json_decode($json, true);
    return $data['commit']['committer']['date'] ?? null;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Atualizador</title>
<style>
body { font-family: sans-serif; padding: 2rem; background: #f8f9fa; }
.container { max-width: 800px; margin:auto; background:#fff; padding:2rem; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1);}
button { padding:.5rem 1rem; font-size:1rem; }
ul { list-style-type: disc; margin-left:20px; }
</style>
</head>
<body>
<div class="container">
<h2>Atualizar Repositório</h2>
<form method="post">
<button name="atualizar" type="submit">🔄 Atualizar</button>
</form>

<p><?= htmlspecialchars($msg) ?></p>

<?php if ($ultimoCommitData): ?>
<p>🕒 Último commit no GitHub: <?= $ultimoCommitData ?></p>
<?php endif; ?>

<h3>📂 Conteúdo atual da pasta:</h3>
<?php if (is_dir($destDir)) listarArquivos($destDir); ?>
</div>
</body>
</html>
