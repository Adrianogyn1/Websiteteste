<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$usuario = "Adrianogyn1";
$repositorio = "Websiteteste";
$branch = "main";

$tmpDir = __DIR__ . "/tmp_update";
$destDir = __DIR__ . "/app";
$zipFile = __DIR__ . "/tmp_repo.zip";
$msg = "";

// Função para obter último commit
function getUltimoCommit($usuario, $repo, $branch) {
    $url = "https://api.github.com/repos/$usuario/$repo/commits/$branch";
    $opts = ["http" => ["header" => "User-Agent: PHP\r\n"]];
    $context = stream_context_create($opts);
    $json = @file_get_contents($url, false, $context);
    if (!$json) return null;
    $data = json_decode($json, true);
    return $data['commit']['committer']['date'] ?? null;
}

$ultimoCommit = getUltimoCommit($usuario, $repositorio, $branch);
$ultimoCommitData = $ultimoCommit ? date("d/m/Y H:i:s", strtotime($ultimoCommit)) : null;

// Função para copiar arquivos recursivamente
function recursiveCopy($src, $dst) {
    if (!is_dir($src)) return;
    if (!is_dir($dst)) mkdir($dst, 0755, true);
    $files = scandir($src);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $srcPath = "$src/$file";
        $dstPath = "$dst/$file";

        // Ignora o próprio script de atualização
        if (basename(__FILE__) === $file && dirname(__FILE__) === $dst) continue;

        if (is_dir($srcPath)) {
            recursiveCopy($srcPath, $dstPath);
        } else {
            @copy($srcPath, $dstPath);
        }
    }
}

// Função para limpar pasta recursivamente
function recursiveDelete($dir) {
    if (!is_dir($dir)) return;
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() ? @rmdir($file) : @unlink($file);
    }
    @rmdir($dir);
}

// Função para listar arquivos
function listarArquivos($dir, $prefixo = "") {
    if (!is_dir($dir)) return;
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

// Atualizar repositório
if (isset($_POST['atualizar'])) {

    // Baixar ZIP
    $zipContent = @file_get_contents("https://github.com/$usuario/$repositorio/archive/refs/heads/$branch.zip");
    if (!$zipContent) {
        $msg = "❌ Falha ao baixar o ZIP. Verifique o usuário, repositório ou branch.";
    } else {
        file_put_contents($zipFile, $zipContent);

        // Limpar pasta temporária
        recursiveDelete($tmpDir);
        mkdir($tmpDir);

        // Extrair ZIP
        $zip = new ZipArchive;
        if ($zip->open($zipFile) === TRUE) {
            $zip->extractTo($tmpDir);
            $zip->close();
            $msg = "✅ ZIP extraído na pasta temporária.";

            // Encontrar pasta dentro do ZIP
            $folders = glob("$tmpDir/*", GLOB_ONLYDIR);
            if (count($folders) > 0) {
                recursiveCopy($folders[0], $destDir);
                $msg .= " ✅ Arquivos movidos para $destDir";
            } else {
                $msg .= " ⚠ Nenhuma pasta encontrada no ZIP.";
            }

        } else {
            $msg = "❌ Falha ao extrair ZIP.";
        }
    }

    // Atualizar último commit após a atualização
    $ultimoCommit = getUltimoCommit($usuario, $repositorio, $branch);
    $ultimoCommitData = $ultimoCommit ? date("d/m/Y H:i:s", strtotime($ultimoCommit)) : null;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Atualizador Seguro</title>
<style>
body { font-family: sans-serif; padding:2rem; background:#f8f9fa; }
.container { max-width:800px; margin:auto; background:#fff; padding:2rem; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1);}
button { padding:.5rem 1rem; font-size:1rem; }
ul { list-style-type: disc; margin-left:20px; }
</style>
</head>
<body>
<div class="container">
<h2>Atualizar Repositório Websiteteste</h2>
<form method="post">
<button name="atualizar" type="submit">🔄 Atualizar</button>
</form>

<p><?= htmlspecialchars($msg) ?></p>

<?php if ($ultimoCommitData): ?>
<p>🕒 Último commit no GitHub: <?= $ultimoCommitData ?></p>
<?php endif; ?>

<h3>📂 Conteúdo atual da pasta:</h3>
<?php listarArquivos($destDir); ?>
</div>
</body>
</html>
