<?php
// Deve ser a primeira linha
session_start();

// --- CONFIGURAÇÕES DO USUÁRIO ---
$cookie_name = 'pla3412'; // Nome da variável interna de cookie (usada para salvar o diretório)
$allowed_extensions = array( 'sqlite', 'sqlite3','db', 'db3');
// --------------------------------

// --- CONFIGURAÇÕES DE AUTENTICAÇÃO ---
$AUTH_USER = 'admin'; // Usuário hardcoded
$AUTH_PASS = '12345'; // Senha hardcoded (RECOMENDADO: mudar e usar hash_password em produção!)
define('AUTH_COOKIE_NAME', 'sqlite_auth');
define('AUTH_TIMEOUT', time() + (3 * 3600)); // Cookie expira em 3 horas

$message = null;
$is_logged_in = false;

// Define o nome da variável real do cookie que armazena o diretório
define('DB_DIR_COOKIE', $cookie_name); 

// --- 1. LÓGICA DE AUTENTICAÇÃO E ACESSO ---

// Checa se o cookie de autenticação existe e é válido
if (isset($_COOKIE[AUTH_COOKIE_NAME]) && $_COOKIE[AUTH_COOKIE_NAME] === hash('sha256', $AUTH_USER . $_SERVER['REMOTE_ADDR'])) {
    $is_logged_in = true;
}

// A. Lógica de Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_user']) && isset($_POST['login_pass'])) {
    $user = trim($_POST['login_user']);
    $pass = trim($_POST['login_pass']);

    if ($user === $AUTH_USER && $pass === $AUTH_PASS) {
        // Gera um token de segurança básico (usuário + IP)
        $token = hash('sha256', $AUTH_USER . $_SERVER['REMOTE_ADDR']);
        setcookie(AUTH_COOKIE_NAME, $token, AUTH_TIMEOUT, "/");
        // Redireciona para evitar reenvio do formulário de login
        header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    } else {
        $message = "<div class='alert alert-danger' role='alert'>Usuário ou senha inválidos.</div>";
    }
}

// B. Lógica de Logout
if (isset($_GET['logout'])) {
    setcookie(AUTH_COOKIE_NAME, '', time() - 3600, "/"); // Expira o cookie
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// C. Captura de Mensagens de Sucesso após Redirecionamento
if (isset($_GET['message'])) {
    $message = "<div class='alert alert-success' role='alert'>" . htmlspecialchars($_GET['message']) . "</div>";
}
if (isset($_GET['error'])) {
    $message = "<div class='alert alert-danger' role='alert'>Erro: " . htmlspecialchars($_GET['error']) . "</div>";
}

// SE NÃO ESTIVER LOGADO, MOSTRA O FORMULÁRIO DE LOGIN E ENCERRA O SCRIPT
if (!$is_logged_in) {
    //include 'login_form.html'; // Usaremos a parte final do HTML para o login
    // Todo o HTML de login será adicionado ao final, no ponto onde o código é impresso
} else {

// ----------------------------------------------------
// --- CÓDIGO PRINCIPAL (SOMENTE SE ESTIVER LOGADO) ---
// ----------------------------------------------------

// 2. Lógica para carregar o diretório
// Prioridade: POST (salvar novo dir) > COOKIE > Valor Padrão
$database_dir = './'; // Valor padrão (pasta atual)

if (isset($_POST['dir_path'])) {
    // 2.1. Processa a submissão do formulário de configuração de diretório
    $new_dir = trim($_POST['dir_path']);
    $new_dir = rtrim($new_dir, '/\\'); 
    
    // Tenta salvar o cookie por 30 dias
    if (setcookie(DB_DIR_COOKIE, $new_dir, time() + (86400 * 30), "/")) {
        $database_dir = $new_dir;
        header('Location: ./?message=' . urlencode("Diretório de trabalho atualizado e salvo em cookie ('" . DB_DIR_COOKIE . "'): " . $database_dir));
        exit;
    } else {
        $database_dir = $new_dir;
        $message = "<div class='alert alert-warning' role='alert'>Diretório de trabalho atualizado, mas o cookie falhou. Usando diretório: <strong>" . htmlspecialchars($database_dir) . "</strong></div>";
    }
} elseif (isset($_COOKIE[DB_DIR_COOKIE]) && !empty($_COOKIE[DB_DIR_COOKIE])) {
    // 2.2. Carrega o diretório do cookie
    $database_dir = rtrim($_COOKIE[DB_DIR_COOKIE], '/\\'); 
}


// 3. Lógica para exclusão de banco de dados (DELETAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_db_name']) && isset($_POST['delete_db_dir'])) {
    $db_to_delete = basename(trim($_POST['delete_db_name']));
    $dir_for_delete = rtrim(trim($_POST['delete_db_dir']), '/\\');
    
    // Security check 1: Validate extension
    $ext = pathinfo($db_to_delete, PATHINFO_EXTENSION);
    if (!in_array(strtolower($ext), $allowed_extensions)) {
        header('Location: ./?error=' . urlencode("Erro de segurança: Extensão de arquivo não permitida para exclusão."));
        exit;
    } else {
        $full_path_to_delete = $dir_for_delete . '/' . $db_to_delete;

        // Security check 2: Ensure the file exists and is within the allowed directory before deleting
        if (file_exists($full_path_to_delete) && strpos(realpath($full_path_to_delete), realpath($dir_for_delete)) === 0) {
            if (unlink($full_path_to_delete)) {
                header('Location: ./?message=' . urlencode("Banco de dados '{$db_to_delete}' excluído com sucesso!"));
                exit;
            } else {
                header('Location: ./?error=' . urlencode("Erro ao excluir o banco de dados '{$db_to_delete}'. Verifique as permissões de escrita."));
                exit;
            }
        } else {
            header('Location: ./?error=' . urlencode("Erro: Arquivo não encontrado ou caminho inválido."));
            exit;
        }
    }
}


// 4. Lógica para renomear banco de dados (RENAME)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['old_db_name']) && isset($_POST['new_db_name_rename'])) {
    $old_name = basename(trim($_POST['old_db_name']));
    $new_name_raw = trim($_POST['new_db_name_rename']);
    
    // 1. Garante que o novo nome tenha a extensão permitida
    $ext = pathinfo($new_name_raw, PATHINFO_EXTENSION);
    if ($ext === '' || !in_array(strtolower($ext), $allowed_extensions)) {
        $new_name = $new_name_raw . '.' . $allowed_extensions[0];
    } else {
        $new_name = $new_name_raw;
    }

    $old_path = $database_dir . '/' . $old_name;
    $new_path = $database_dir . '/' . $new_name;
    
    // Validação de segurança e existência
    if (!file_exists($old_path)) {
        header('Location: ./?error=' . urlencode("Erro: Arquivo original '{$old_name}' não encontrado."));
        exit;
    } elseif (file_exists($new_path) && $old_path !== $new_path) {
        header('Location: ./?error=' . urlencode("Erro: Já existe um arquivo chamado '{$new_name}' no diretório."));
        exit;
    } elseif (rename($old_path, $new_path)) {
        header('Location: ./?message=' . urlencode("Banco de dados '{$old_name}' renomeado com sucesso para '{$new_name}'!"));
        exit;
    } else {
        header('Location: ./?error=' . urlencode("Erro ao renomear o arquivo. Verifique as permissões de escrita no diretório."));
        exit;
    }
}


// 5. Lógica para criação de novo banco de dados (usa $database_dir atual)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_db_name'])) {
    $new_name = trim($_POST['new_db_name']);
    if (!empty($new_name)) {
        
        $ext = pathinfo($new_name, PATHINFO_EXTENSION);
        if ($ext === '' || !in_array(strtolower($ext), $allowed_extensions)) {
            $new_name .= '.' . $allowed_extensions[0];
        }
        
        $full_path = $database_dir . '/' . $new_name;

        try {
            if (!is_dir($database_dir) || !is_writable($database_dir)) {
                 throw new PDOException("O diretório '{$database_dir}' não existe ou não tem permissão de escrita. Por favor, ajuste em Configurações.");
            }

            $pdo = new PDO("sqlite:{$full_path}");
            $pdo = null;
            header('Location: ./?message=' . urlencode("Banco de dados '{$new_name}' criado com sucesso no diretório " . $database_dir . "!"));
            exit;
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger' role='alert'>Erro ao criar banco de dados: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// 6. Lógica para listar os bancos de dados (usa $database_dir atual e extensões permitidas)
$databases = [];
$ext_pattern = implode(',', $allowed_extensions);

if (is_dir($database_dir)) {
    // Busca arquivos com a lista de extensões permitidas
    $files = glob($database_dir . '/*.{' . $ext_pattern . '}', GLOB_BRACE);
    foreach ($files as $file) {
        // Apenas o nome do arquivo deve ser armazenado na lista
        $databases[] = basename($file);
    }
} else {
    $message = "<div class='alert alert-warning' role='alert'>Atenção: O diretório configurado (<strong>" . htmlspecialchars($database_dir) . "</strong>) não existe ou é inacessível. Clique em **Alterar** para corrigir.</div>";
}
sort($databases);
} // Fim do bloco IF $is_logged_in

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>SQLite Admin - Index de Bancos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f4f7f6; }
        .container-main { margin-top: 20px; }
        .list-group-item { display: flex; justify-content: space-between; align-items: center; }
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    
    <?php if (!$is_logged_in): ?>
        <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="card shadow" style="width: 100%; max-width: 400px;">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0"><i class="bi bi-lock-fill"></i> SQLite Admin Login</h4>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="login_user" class="form-label">Usuário:</label>
                            <input type="text" name="login_user" id="login_user" class="form-control" required value="<?php echo htmlspecialchars($AUTH_USER); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="login_pass" class="form-label">Senha:</label>
                            <input type="password" name="login_pass" id="login_pass" class="form-control" required value="<?php /*echo htmlspecialchars($AUTH_PASS);*/ ?>">
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right"></i> Entrar</button>
                    </form>
                    <p class="mt-3 text-center small text-muted">A sessão dura 3 horas.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="container container-main">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                <h1 class="text-primary mb-0">SQLite Admin - Index de Bancos de Dados</h1>
                <a href="?logout=1" class="btn btn-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Logout (<?php echo htmlspecialchars($AUTH_USER); ?>)</a>
            </div>
            
            <?php echo $message; ?>

            <div class="alert alert-secondary small d-flex justify-content-between align-items-center mb-3">
                <div>
                    <i class="bi bi-folder-fill me-2"></i> Diretório de Trabalho Atual: <strong><?php echo htmlspecialchars($database_dir); ?></strong>
                    <span class="badge bg-primary ms-2">Salvo em Cookie (<?php echo DB_DIR_COOKIE; ?>)</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#configDirModal">
                    <i class="bi bi-gear"></i> Alterar
                </button>
            </div>

            <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#createDbModal">
                    <i class="bi bi-plus-circle"></i> Criar Novo Banco
                </button>
                <button type="button" class="btn btn-primary" onclick="window.location.reload();">
                    <i class="bi bi-arrow-clockwise"></i> Recarregar Lista
                </button>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Bancos de Dados Encontrados (Extensões: <?php echo htmlspecialchars($ext_pattern); ?>)</h2>
                    <input type="text" id="dbSearch" class="form-control w-25" placeholder="Procurar banco..." aria-label="Procurar banco">
                </div>
                <ul class="list-group list-group-flush" id="dbList">
                    <?php if (!empty($databases)): ?>
                        <?php foreach ($databases as $db_name): ?>
                            <li class="list-group-item db-item">
                                <span class="fw-bold"><?php echo htmlspecialchars($db_name); ?></span>
                                <div>
                                    <a href="table_editor.php?db=<?php echo urlencode($database_dir."/".$db_name); ?>&dir=<?php echo urlencode($database_dir); ?>" class="btn btn-sm btn-primary me-2"><i class="bi bi-gear"></i> Gerenciar</a>
                                    
                                    <button type="button" class="btn btn-sm btn-warning me-2" data-bs-toggle="modal" data-bs-target="#renameDbModal" data-db-name="<?php echo htmlspecialchars($db_name); ?>">
                                        <i class="bi bi-pencil-square"></i> Renomear
                                    </button>

                                    <button type="button" class="btn btn-sm btn-info me-2" data-bs-toggle="modal" data-bs-target="#exportModal" data-db-name="<?php echo htmlspecialchars($db_name); ?>" data-db-file="<?php echo urlencode($db_name); ?>" data-db-dir="<?php echo urlencode($database_dir); ?>">
                                        <i class="bi bi-file-earmark-code"></i> Exportar SQL
                                    </button>
                                    
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteDbModal" data-db-name="<?php echo htmlspecialchars($db_name); ?>" data-db-dir="<?php echo htmlspecialchars($database_dir); ?>">
                                        <i class="bi bi-trash"></i> Excluir
                                    </button>
                                    
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-muted empty-list">Nenhum arquivo SQLite encontrado em <strong><?php echo htmlspecialchars($database_dir); ?></strong> com as extensões permitidas.</li>
                    <?php endif; ?>
                </ul>
            </div>
            
        </div>

        <div class="modal fade" id="createDbModal" tabindex="-1" aria-labelledby="createDbModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="createDbModalLabel"><i class="bi bi-plus-circle"></i> Criar Novo Banco de Dados</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST"> 
                        <div class="modal-body">
                            <div class="alert alert-info small">O banco será criado no diretório: <strong><?php echo htmlspecialchars($database_dir); ?></strong></div>
                            <div class="mb-3">
                                <label for="new_db_name_input" class="form-label">Nome do Arquivo SQLite</label>
                                <input type="text" name="new_db_name" id="new_db_name_input" class="form-control" placeholder="Ex: clientes.sqlite ou app.db" required>
                                <div class="form-text">Se nenhuma extensão for fornecida, será usada a extensão `<?php echo $allowed_extensions[0]; ?>`.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle"></i> Criar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="renameDbModal" tabindex="-1" aria-labelledby="renameDbModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="renameDbModalLabel"><i class="bi bi-pencil-square"></i> Renomear Banco de Dados</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="old_db_name" id="modal-rename-old-name">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nome Atual:</label>
                                <p id="modal-rename-db-display" class="form-control-plaintext text-muted"></p>
                            </div>
                            <div class="mb-3">
                                <label for="new_db_name_rename_input" class="form-label fw-bold">Novo Nome do Arquivo SQLite:</label>
                                <input type="text" name="new_db_name_rename" id="new_db_name_rename_input" class="form-control" required>
                                <div class="form-text">O arquivo será movido/renomeado. Use a mesma extensão ou será adicionada `<?php echo $allowed_extensions[0]; ?>`.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Renomear</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="configDirModal" tabindex="-1" aria-labelledby="configDirModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title" id="configDirModalLabel"><i class="bi bi-folder-fill"></i> Configurar Diretório de Trabalho</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <p class="lead">Defina o caminho (path) para onde você deseja buscar e salvar seus arquivos SQLite.</p>
                            <div class="mb-3">
                                <label for="dir_path_input" class="form-label fw-bold">Caminho do Diretório:</label>
                                <input type="text" name="dir_path" id="dir_path_input" class="form-control" value="<?php echo htmlspecialchars($database_dir); ?>" placeholder="Ex: /var/www/dados/ ou ../dados" required>
                                <div class="form-text">
                                    Este valor será salvo no cookie `<?php echo DB_DIR_COOKIE; ?>`.
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Salvar Preferência</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="modal fade" id="deleteDbModal" tabindex="-1" aria-labelledby="deleteDbModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="delete-form" method="POST">
                        <input type="hidden" name="delete_db_name" id="modal-delete-db-name">
                        <input type="hidden" name="delete_db_dir" id="modal-delete-db-dir">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="deleteDbModalLabel"><i class="bi bi-exclamation-triangle-fill"></i> CONFIRMAR EXCLUSÃO</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="lead">Você tem certeza que deseja **excluir permanentemente** o seguinte banco de dados?</p>
                            <p class="fw-bold fs-5 text-danger" id="modal-delete-db-display"></p>
                            <p class="small">Esta ação não pode ser desfeita. O arquivo será removido do diretório: <strong id="modal-delete-dir-display"></strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Sim, Excluir</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="export-form" method="GET" action="export_full_sql.php" target="_blank">
                        <input type="hidden" name="db" id="modal-db-file">
                        <input type="hidden" name="dir" id="modal-db-dir">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title" id="exportModalLabel"><i class="bi bi-file-earmark-code"></i> Configurar Exportação SQL</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="lead">Exportando banco: <strong id="modal-db-name-display"></strong></p>

                            <div class="mb-3 border p-3 rounded">
                                <label class="form-label fw-bold">Conteúdo a Exportar:</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="export_type" id="export-structure-data" value="both" checked>
                                    <label class="form-check-label" for="export-structure-data">Estrutura e Dados (CREATE e INSERT)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="export_type" id="export-structure" value="structure">
                                    <label class="form-check-label" for="export-structure">Somente Estrutura</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="export_type" id="export-data" value="data">
                                    <label class="form-check-label" for="export-data">Somente Dados</label>
                                </div>
                            </div>
                            
                            <div id="tables-list-container" class="mt-3">
                                <div class="text-center text-muted">Carregando lista de tabelas...</div>
                            </div>
                            
                            <div class="alert alert-warning small mt-3">
                                O arquivo SQL gerado será formatado para máxima compatibilidade com MySQL/MariaDB.
                            </div>
                            
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-info text-white"><i class="bi bi-download"></i> Baixar Arquivo .SQL</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>


    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Apenas executa a lógica JS se o conteúdo principal estiver visível
            if (!document.querySelector('.container.d-flex')) {
                const exportModal = document.getElementById('exportModal');
                const tablesContainer = document.getElementById('tables-list-container');
                const exportForm = document.getElementById('export-form');

                // ----------------------------------------------------
                // LÓGICA DE FILTRO/PESQUISA
                // ----------------------------------------------------
                const dbSearchInput = document.getElementById('dbSearch');
                const dbList = document.getElementById('dbList');
                // Verifica se dbList existe antes de tentar selecionar
                if (dbList) {
                    const dbItems = dbList.querySelectorAll('.db-item');

                    dbSearchInput.addEventListener('keyup', function() {
                        const filter = dbSearchInput.value.toLowerCase();
                        let found = false;

                        dbItems.forEach(function(item) {
                            const dbName = item.querySelector('span.fw-bold').textContent.toLowerCase();
                            if (dbName.includes(filter)) {
                                item.style.display = 'flex';
                                found = true;
                            } else {
                                item.style.display = 'none';
                            }
                        });

                        const emptyMessage = dbList.querySelector('.empty-list');
                        if (emptyMessage) {
                            emptyMessage.style.display = (found || filter === '') ? 'none' : 'block';
                        }
                    });
                }
                
                // ----------------------------------------------------
                // LÓGICA DO NOVO MODAL DE RENOMEAR
                // ----------------------------------------------------
                const renameModal = document.getElementById('renameDbModal');
                if (renameModal) {
                    renameModal.addEventListener('show.bs.modal', function (event) {
                        const button = event.relatedTarget;
                        const dbName = button.getAttribute('data-db-name');
                        const ext = dbName.split('.').pop();
                        const nameWithoutExt = dbName.substring(0, dbName.lastIndexOf('.')) || dbName;

                        document.getElementById('modal-rename-old-name').value = dbName;
                        document.getElementById('modal-rename-db-display').textContent = dbName;
                        
                        // Pré-preenche o novo campo de nome com o nome atual (sem extensão, para o usuário digitar)
                        document.getElementById('new_db_name_rename_input').value = dbName;
                        // Opcional: foca no campo
                        setTimeout(() => document.getElementById('new_db_name_rename_input').select(), 100);
                    });
                }

                // ----------------------------------------------------
                // LÓGICA DO MODAL DE EXCLUSÃO
                // ----------------------------------------------------
                const deleteModal = document.getElementById('deleteDbModal');
                if (deleteModal) {
                    deleteModal.addEventListener('show.bs.modal', function (event) {
                        const button = event.relatedTarget;
                        const dbName = button.getAttribute('data-db-name');
                        const dbDir = button.getAttribute('data-db-dir');

                        document.getElementById('modal-delete-db-name').value = dbName;
                        document.getElementById('modal-delete-db-dir').value = dbDir;
                        document.getElementById('modal-delete-db-display').textContent = dbName;
                        document.getElementById('modal-delete-dir-display').textContent = dbDir;
                    });
                }


                // ----------------------------------------------------
                // LÓGICA DO MODAL DE EXPORTAÇÃO
                // ----------------------------------------------------
                if (exportModal) {
                    exportModal.addEventListener('show.bs.modal', function (event) {
                        const button = event.relatedTarget;
                        const dbName = button.getAttribute('data-db-name');
                        const dbFile = button.getAttribute('data-db-file');
                        const dbDir = button.getAttribute('data-db-dir');

                        document.getElementById('modal-db-name-display').textContent = dbName;
                        document.getElementById('modal-db-file').value = dbFile;
                        document.getElementById('modal-db-dir').value = dbDir;

                        tablesContainer.innerHTML = '<div class="text-center text-muted"><i class="bi bi-arrow-clockwise spin"></i> Carregando lista de tabelas...</div>';

                        $.ajax({
                            url: 'get_tables.php',
                            type: 'GET',
                            data: { 
                                db: dbFile, 
                                dir: dbDir
                            },
                            success: function(data) {
                                tablesContainer.innerHTML = data;
                                
                                $('#select-all-tables').on('change', function() {
                                    $('.table-checkbox').prop('checked', $(this).prop('checked'));
                                });

                                $('.table-checkbox').on('change', function() {
                                    if (!$(this).prop('checked')) {
                                        $('#select-all-tables').prop('checked', false);
                                    } else {
                                        if ($('.table-checkbox:checked').length === $('.table-checkbox').length) {
                                            $('#select-all-tables').prop('checked', true);
                                        }
                                    }
                                });
                            },
                            error: function() {
                                tablesContainer.innerHTML = '<p class="alert alert-danger mb-0">Erro ao carregar as tabelas.</p>';
                            }
                        });
                    });
                }
                
                if (exportForm) {
                    exportForm.addEventListener('submit', function(e) {
                        if ($('.table-checkbox:checked').length === 0) {
                            e.preventDefault();
                            alert('Por favor, selecione pelo menos uma tabela para exportar.');
                            $('#exportModal').modal('handleUpdate'); 
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>