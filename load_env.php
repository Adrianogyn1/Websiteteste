<?php

/**
 * Carrega variáveis de ambiente de um arquivo .env para $_ENV, $_SERVER e putenv().
 *
 * @param string $path O caminho completo para o arquivo .env
 * @return bool Retorna true se o arquivo foi carregado, false caso contrário.
 */
function loadEnv($path) {
    // 1. Verifica se o arquivo .env existe
    if (!file_exists($path)) {
        return false;
    }

    // 2. Lê o arquivo linha por linha, ignorando linhas vazias
    // FILE_SKIP_EMPTY_LINES garante que linhas vazias não sejam lidas.
    // O PHP mantém o caractere de nova linha (\n) em cada item do array.
    $lines = file($path, FILE_SKIP_EMPTY_LINES);
    
    if ($lines === false) {
        return false; // Falha na leitura do arquivo
    }

    foreach ($lines as $line) {
        // Remove espaços em branco e o caractere de nova linha (\n ou \r\n) da linha
        $line = trim($line); 

        // 3. Ignora linhas que são comentários
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        // 4. Garante que há um sinal de '=' para dividir chave e valor
        if (strpos($line, '=') === false) {
             continue;
        }
        
        // Divide a linha em chave e valor no primeiro sinal de '='
        list($name, $value) = explode('=', $line, 2);

        $name = trim($name);
        $value = trim($value);

        // 5. Remove aspas simples ou duplas do valor, se houver
        // Usa regex para ser mais robusto na remoção de aspas que envolvem o valor
        if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match("/^'(.*)'$/", $value, $matches)) {
            // Usa o conteúdo capturado dentro das aspas
            $value = $matches[1];
        }

        // 6. Define a variável de ambiente (putenv é a maneira mais confiável para scripts CLI)
        putenv(sprintf('%s=%s', $name, $value));
        
        // 7. Popular $_ENV e $_SERVER (essencial para acesso via web)
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    return true;
}

// --- Uso no seu script principal (ex: index.php ou bootstrap.php) ---

// Chama a função para carregar o arquivo .env na raiz do projeto
// __DIR__ é o diretório onde este arquivo de script está.
$envLoaded = loadEnv(__DIR__ . '/.env');

if (!$envLoaded) {
    // Opcional: Tratar o caso de falha na leitura ou arquivo .env ausente
    // die("Erro: O arquivo .env não foi encontrado ou não pôde ser lido.");
}

// --- Teste (Opcional) ---
/**
echo "--- Variáveis Carregadas ---\n";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NÃO ENCONTRADO') . "\n";
echo "API_KEY: " . (getenv('API_KEY') ?? 'NÃO ENCONTRADO') . "\n";
echo "URL_SITE: " . ($_SERVER['URL_SITE'] ?? 'NÃO ENCONTRADO') . "\n";
echo "NODE_CHAT: " . ($_ENV['NODE_CHAT'] ?? 'NÃO ENCONTRADO') . "\n";
/**/
?>