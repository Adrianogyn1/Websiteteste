<?php
// Arquivo: dados.php (Este arquivo é incluído em table_editor.php. $pdo, $current_table, $db_param, $table_param JÁ ESTÃO DEFINIDOS.)

if (!isset($pdo) || !isset($current_table)) {
    die('<div class="alert alert-danger">Erro: Dados.php não pode ser carregado diretamente.</div>');
}

// -----------------------------------------------------------------
// FUNÇÕES DE AJUDA
// -----------------------------------------------------------------

/**
 * Obtém a estrutura (colunas e PK) de uma tabela.
 */
function get_table_structure_and_pk($pdo, $table) {
    $stmt = $pdo->prepare("PRAGMA table_info(\"$table\")");
    $stmt->execute();
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $pk_columns = [];
    foreach ($structure as $col) {
        if ($col['pk']) {
            $pk_columns[] = $col['name'];
        }
    }
    
    return [
        'structure' => $structure,
        'pk_columns' => $pk_columns,
        'column_names' => array_column($structure, 'name')
    ];
}

// -----------------------------------------------------------------
// LÓGICA DE MANIPULAÇÃO DE DADOS (POST)
// -----------------------------------------------------------------

$table_info = get_table_structure_and_pk($pdo, $current_table);
$pk_columns = $table_info['pk_columns'];
$column_names = $table_info['column_names'];

// --- VARIÁVEIS DE ESTADO DA PAGINAÇÃO PARA REDIRECIONAMENTO ---
$limit_param_url = isset($_GET['limit']) ? '&limit=' . $_GET['limit'] : '';
$offset_param_url = isset($_GET['offset']) ? '&offset=' . $_GET['offset'] : '';

// REDIRECIONAMENTO DE ERRO/SUCESSO (MANTÉM O ESTADO DA PAGINAÇÃO)
$base_redirect = "Location: table_editor.php?db=$db_param&table=$table_param&module=dados{$limit_param_url}{$offset_param_url}";
$error_redirect = $base_redirect . "&error=";
$success_redirect = $base_redirect . "&message=";
// ------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    
    try {
        // --- AÇÃO DE DELETAR LINHA ---
        if ($_POST['action_type'] === 'delete_row' && isset($_POST['row_identifier'])) {
            
            $identifiers = json_decode($_POST['row_identifier'], true);

            if (empty($identifiers)) {
                throw new Exception("Identificador de linha para exclusão não fornecido.");
            }

            $where_clauses = [];
            foreach ($identifiers as $col => $value) {
                $where_clauses[] = $pdo->quote($col) . " = " . $pdo->quote($value);
            }
            
            $sql = "DELETE FROM \"$current_table\" WHERE " . implode(' AND ', $where_clauses);
            $pdo->exec($sql);
            
            header($success_redirect . urlencode("Registro excluído com sucesso."));
            exit;
            
        // --- AÇÃO DE ATUALIZAR LINHA ---
        } elseif ($_POST['action_type'] === 'update_row' && isset($_POST['data'], $_POST['original_identifiers'])) {
            
            $new_data = $_POST['data'];
            $original_identifiers = json_decode($_POST['original_identifiers'], true);

            if (empty($original_identifiers) || empty($new_data)) {
                throw new Exception("Dados de atualização ou identificadores originais não fornecidos.");
            }
            
            $set_clauses = [];
            foreach ($new_data as $col => $value) {
                $set_clauses[] = $pdo->quote($col) . " = " . $pdo->quote($value);
            }

            $where_clauses = [];
            foreach ($original_identifiers as $col => $value) {
                $where_clauses[] = $pdo->quote($col) . " = " . $pdo->quote($value);
            }
            
            $sql = "UPDATE \"$current_table\" SET " . implode(', ', $set_clauses) . " WHERE " . implode(' AND ', $where_clauses);
            $pdo->exec($sql);
            
            header($success_redirect . urlencode("Registro atualizado com sucesso."));
            exit;
            
        // --- AÇÃO DE INSERIR LINHA (NOVO) ---
        } elseif ($_POST['action_type'] === 'insert_row' && isset($_POST['data'])) {
            
            $insert_data = $_POST['data'];
            
            $data_to_insert = [];
            foreach ($insert_data as $col => $value) {
                // Filtra e trata strings vazias como NULL para SQLite usar o valor DEFAULT ou NULL
                if (in_array($col, $column_names)) {
                    $data_to_insert[$col] = (trim((string)$value) === '') ? null : $value;
                }
            }

            if (empty($data_to_insert)) {
                 throw new Exception("Nenhum dado válido para inserção fornecido.");
            }
            
            // 2. Constrói a query com named placeholders
            $cols = array_keys($data_to_insert);
            $quoted_cols = array_map(fn($c) => "\"$c\"", $cols);
            
            // Cria ':col1, :col2, ...'
            $named_placeholders = array_map(fn($c) => ":$c", $cols); 

            $sql = "INSERT INTO \"$current_table\" (" . implode(', ', $quoted_cols) . ") VALUES (" . implode(', ', $named_placeholders) . ")";
            
            $stmt = $pdo->prepare($sql);

            // 3. Bind dos valores
            foreach ($data_to_insert as $col => $value) {
                $param_type = is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR;
                // Usa bindValue para ligar o nome do placeholder ao valor e tipo
                $stmt->bindValue(":$col", $value, $param_type);
            }
            
            $stmt->execute();

            header($success_redirect . urlencode("Novo registro inserido com sucesso."));
            exit;
        }

    } catch (Exception $e) {
        header($error_redirect . urlencode($e->getMessage()));
        exit;
    }
}


// -----------------------------------------------------------------
// RENDERIZAÇÃO E PAGINAÇÃO
// -----------------------------------------------------------------

// Configuração de Paginação
$default_limit = 100;
$allowed_limits = [50, 100, 500, 1000, 5000]; 
$limit_param = isset($_GET['limit']) ? $_GET['limit'] : $default_limit;

if ($limit_param === 'all') {
    $limit = null; 
} elseif (is_numeric($limit_param) && $limit_param > 0) {
    $limit = (int)$limit_param;
    if (!in_array($limit, $allowed_limits)) {
        $limit = $default_limit; 
    }
} else {
    $limit = $default_limit;
}

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$current_limit_display = $limit ?? 'all';


try {
    // 1. Conta o total de registros
    $stmt_count = $pdo->query("SELECT COUNT(*) FROM \"$current_table\"") or die(print_r($pdo->errorInfo(), true));
    $total_rows = $stmt_count->fetchColumn();

    // 2. Busca os dados da página atual
    $limit_sql = ($limit === null) ? '' : " LIMIT $limit OFFSET $offset";
    $sql = "SELECT * FROM \"$current_table\"" . $limit_sql;
    
    $stmt = $pdo->query($sql);
    $data_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Nomes das colunas para o cabeçalho
    if (empty($data_rows)) {
        $display_columns = $column_names;
    } else {
        $display_columns = array_keys($data_rows[0]);
    }

} catch (Exception $e) {
    $error = "Erro ao carregar dados: " . $e->getMessage();
    $data_rows = [];
    $display_columns = [];
    $total_rows = 0;
}

?>

<script type="application/json" id="table-structure-json">
    <?php echo json_encode($table_info['structure']); ?>
</script>

<div class="row">
    <div class="col-12">
        <h2 class="h4">Dados da Tabela: <?php echo htmlspecialchars($current_table); ?></h2>
        
        <?php if (empty($pk_columns)): ?>
            <div class="alert alert-warning py-2" role="alert">
                <i class="bi bi-exclamation-triangle"></i> **Aviso:** A tabela não possui PRIMARY KEY. A edição e exclusão de linhas usarão todos os valores da linha como identificadores, o que pode falhar em colunas longas ou com dados repetidos.
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between mb-3 align-items-start">
            <div>
                <p class="text-muted mb-1">Total de Registros: **<?php echo number_format($total_rows, 0, ',', '.'); ?>**</p>
                
                <form method="GET" class="d-flex align-items-center" id="limit-form">
                    <input type="hidden" name="db" value="<?php echo $db_param; ?>">
                    <input type="hidden" name="table" value="<?php echo $table_param; ?>">
                    <input type="hidden" name="module" value="dados">
                    <input type="hidden" name="offset" value="0"> <label for="limit-select" class="form-label mb-0 me-2 small">Itens por Página:</label>
                    <select name="limit" id="limit-select" class="form-select form-select-sm" onchange="document.getElementById('limit-form').submit();">
                        <?php 
                            foreach ($allowed_limits as $opt) {
                                $selected = ($current_limit_display == $opt) ? 'selected' : '';
                                echo "<option value=\"$opt\" $selected>$opt</option>";
                            }
                            $selected_all = ($current_limit_display === 'all') ? 'selected' : '';
                            echo "<option value=\"all\" $selected_all>Todos</option>";
                        ?>
                    </select>
                </form>
            </div>
            
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#insertRowModal"><i class="bi bi-plus-circle"></i> Inserir Novo Registro</button>
        </div>
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-hover table-sm">
                <thead class="table-light sticky-top">
                    <tr>
                        <?php foreach ($display_columns as $col): ?>
                            <th><?php echo htmlspecialchars($col); ?></th>
                        <?php endforeach; ?>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data_rows)): ?>
                        <?php foreach ($data_rows as $row): ?>
                            <tr>
                                <?php 
                                    $row_identifiers = [];
                                    $pk_exists = !empty($pk_columns);
                                    
                                    foreach ($row as $col_name => $col_value) {
                                        if ($pk_exists) {
                                            if (in_array($col_name, $pk_columns)) {
                                                $row_identifiers[$col_name] = $col_value;
                                            }
                                        } else {
                                            $row_identifiers[$col_name] = $col_value;
                                        }
                                        echo '<td>' . htmlspecialchars($col_value ?? 'NULL') . '</td>';
                                    }

                                    $row_identifiers_json = htmlspecialchars(json_encode($row_identifiers), ENT_QUOTES, 'UTF-8');
                                    $row_data_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                ?>
                                
                                <td class="text-center" style="width: 120px;">
                                    <button class="btn btn-sm btn-info text-white edit-row-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editRowModal" 
                                            data-row-identifiers='<?php echo $row_identifiers_json; ?>'
                                            data-row-data='<?php echo $row_data_json; ?>'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    
                                    <button class="btn btn-sm btn-danger delete-row-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteRowModal"
                                            data-row-identifiers='<?php echo $row_identifiers_json; ?>'>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="<?php echo count($display_columns) + 1; ?>" class="text-center text-muted">Nenhum dado encontrado nesta tabela.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php 
        if ($limit !== null && $total_rows > $limit): 
            $current_page = floor($offset / $limit) + 1;
            $total_pages = ceil($total_rows / $limit);
            // URL base inclui o limite
            $url_base = "table_editor.php?db=$db_param&table=$table_param&module=dados&limit=$limit";
        ?>
            <nav aria-label="Paginação">
                <ul class="pagination pagination-sm justify-content-center">
                    
                    <li class="page-item <?php echo $offset == 0 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo $offset == 0 ? '#' : $url_base . '&offset=' . max(0, $offset - $limit); ?>">Anterior</a>
                    </li>
                    
                    <li class="page-item active"><span class="page-link"><?php echo $current_page; ?> de <?php echo $total_pages; ?></span></li>
                    
                    <li class="page-item <?php echo ($offset + $limit) >= $total_rows ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo ($offset + $limit) >= $total_rows ? '#' : $url_base . '&offset=' . ($offset + $limit); ?>">Próximo</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="insertRowModal" tabindex="-1" aria-labelledby="insertRowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="table_editor.php?db=<?php echo $db_param; ?>&table=<?php echo $table_param; ?>&module=dados">
                <input type="hidden" name="action_type" value="insert_row">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="insertRowModalLabel"><i class="bi bi-plus-circle"></i> Inserir Novo Registro em **<?php echo htmlspecialchars($current_table); ?>**</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="insert-form-body">
                    <div class="text-center text-muted">Aguarde... Carregando estrutura da tabela.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Inserir Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editRowModal" tabindex="-1" aria-labelledby="editRowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="table_editor.php?db=<?php echo $db_param; ?>&table=<?php echo $table_param; ?>&module=dados">
                <input type="hidden" name="action_type" value="update_row">
                <input type="hidden" name="original_identifiers" id="edit-original-identifiers">

                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="editRowModalLabel"><i class="bi bi-pencil"></i> Editando Registro em **<?php echo htmlspecialchars($current_table); ?>**</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="edit-form-body">
                    <div class="text-center text-muted">Carregando campos...</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info text-white"><i class="bi bi-save"></i> Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteRowModal" tabindex="-1" aria-labelledby="deleteRowModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="table_editor.php?db=<?php echo $db_param; ?>&table=<?php echo $table_param; ?>&module=dados">
                <input type="hidden" name="action_type" value="delete_row">
                <input type="hidden" name="row_identifier" id="delete-row-identifier">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteRowModalLabel"><i class="bi bi-trash"></i> Confirmar Exclusão</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="lead">Tem certeza que deseja **excluir permanentemente** este registro?</p>
                    <div class="alert alert-info py-2 small" id="delete-identifiers-display"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Sim, Deletar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        const editModal = document.getElementById('editRowModal');
        const deleteModal = document.getElementById('deleteRowModal');
        const insertModal = document.getElementById('insertRowModal');
        
        const editFormBody = document.getElementById('edit-form-body');
        const insertFormBody = document.getElementById('insert-form-body');

        // -----------------------------------------------------------------
        // JS para o Modal de Inserção (NOVO)
        // -----------------------------------------------------------------
        insertModal.addEventListener('show.bs.modal', function (event) {
            
            // Lê a estrutura da tabela do elemento JSON (injetado pelo PHP)
            const structureElement = document.getElementById('table-structure-json');
            if (!structureElement) {
                insertFormBody.innerHTML = '<div class="alert alert-danger">Erro: Estrutura da tabela não encontrada.</div>';
                return;
            }
            const columnStructure = JSON.parse(structureElement.textContent);

            let formHtml = '';
            columnStructure.forEach(col => {
                const isPk = col.pk;
                const isNotNull = col.notnull;
                const type = col.type;
                const defaultValue = col.dflt_value === null ? 'NULL' : col.dflt_value;
                const placeholderText = `Tipo: ${type}, Padrão: ${defaultValue} ${isNotNull ? '(Obrigatório)' : ''}`;
                
                // Tenta definir o tipo de input baseado no tipo SQLite
                let inputType = 'text';
                if (type.toUpperCase().includes('INTEGER') || type.toUpperCase().includes('REAL')) {
                    inputType = 'number';
                }
                
                // Colunas AUTOINCREMENT (ID com PK INTEGER e AI) devem ser deixadas vazias para o banco gerá-las
                // Vamos supor que se for PK INTEGER, o usuário deve deixar o campo vazio para autoincremento.
                const isPkInteger = isPk && type.toUpperCase() === 'INTEGER'; 
                const valueAttr = isPkInteger ? 'value=""' : 'value=""'; 

                formHtml += `
                    <div class="mb-3">
                        <label for="insert-${col.name}" class="form-label">${col.name} 
                            ${isPk ? '<span class="badge bg-primary">PK</span>' : ''} 
                            ${isNotNull ? '<span class="badge bg-danger">NN</span>' : ''}
                        </label>
                        <input type="${inputType}" 
                                name="data[${col.name}]" 
                                id="insert-${col.name}" 
                                class="form-control form-control-sm" 
                                placeholder="${placeholderText}"
                                ${isNotNull && !isPkInteger ? ' required' : ''}
                                ${valueAttr}
                        >
                        <small class="form-text text-muted">${placeholderText}. ${isPkInteger ? 'Deixe em branco para AUTOINCREMENTO.' : ''}</small>
                    </div>
                `;
            });

            insertFormBody.innerHTML = formHtml;
        });


        // -----------------------------------------------------------------
        // JS para o Modal de Edição
        // -----------------------------------------------------------------
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; 
            const rowDataJson = button.getAttribute('data-row-data');
            const rowIdentifiersJson = button.getAttribute('data-row-identifiers');
            
            const rowData = JSON.parse(rowDataJson);
            
            document.getElementById('edit-original-identifiers').value = rowIdentifiersJson;
            
            let formHtml = '';
            for (const [columnName, value] of Object.entries(rowData)) {
                
                const isIdentifier = rowIdentifiersJson.includes(`"${columnName}"`);
                const isPkClass = isIdentifier ? 'border-primary' : '';
                
                formHtml += `
                    <div class="mb-3">
                        <label for="edit-${columnName}" class="form-label">${columnName} ${isIdentifier ? ' <span class="badge bg-primary">ID</span>' : ''}</label>
                        <input type="text" 
                                name="data[${columnName}]" 
                                id="edit-${columnName}" 
                                class="form-control form-control-sm ${isPkClass}" 
                                value="${(value !== null) ? value : ''}"
                                ${isIdentifier ? ' readonly' : ''} 
                                placeholder="${isIdentifier ? 'Identificador Original' : 'Novo Valor'}"
                        >
                        ${isIdentifier ? '<small class="form-text text-muted">Este é um identificador e está definido como **somente leitura**.</small>' : ''}
                    </div>
                `;
            }

            editFormBody.innerHTML = formHtml;
        });

        // -----------------------------------------------------------------
        // JS para o Modal de Exclusão
        // -----------------------------------------------------------------
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const rowIdentifiersJson = button.getAttribute('data-row-identifiers');
            const rowIdentifiers = JSON.parse(rowIdentifiersJson);
            
            document.getElementById('delete-row-identifier').value = rowIdentifiersJson;
            
            let displayHtml = '<strong>Identificadores:</strong><br>';
            for (const [col, val] of Object.entries(rowIdentifiers)) {
                displayHtml += `**${col}**: ${val}<br>`;
            }
            document.getElementById('delete-identifiers-display').innerHTML = displayHtml;
        });
    });
</script>