<?php
// Este arquivo é incluído em table_editor.php. Variáveis como $pdo, $current_table, $db_param, $table_param JÁ ESTÃO DEFINIDAS.

if (!isset($pdo) || !isset($current_table)) {
    die('<div class="alert alert-danger">Erro: Estrutura.php não pode ser carregado diretamente.</div>');
}

// -----------------------------------------------------------------
// FUNÇÕES DE ESTRUTURA
// -----------------------------------------------------------------

/**
 * Obtém a estrutura (colunas) de uma tabela.
 */
function get_table_structure($pdo, $table) {
    $stmt = $pdo->prepare("PRAGMA table_info(\"$table\")");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// -----------------------------------------------------------------
// LÓGICA DE ALTER TABLE (POST)
// -----------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    
    // REDIRECIONAMENTO DE ERRO/SUCESSO
    $error_redirect = "Location: table_editor.php?db=$db_param&table=$table_param&module=estrutura&error=";
    $success_redirect = "Location: table_editor.php?db=$db_param&table=$table_param&module=estrutura&message=";
    $conversion_message = ""; // Inicializa a mensagem de conversão

    try {
        if ($_POST['action_type'] === 'rename_column' && isset($_POST['old_col_name']) && isset($_POST['new_col_name'])) {
            $old_col = trim($_POST['old_col_name']);
            $new_col = trim($_POST['new_col_name']);

            if (empty($old_col) || empty($new_col)) {
                throw new Exception("Nomes de coluna antigos e novos são obrigatórios.");
            }
            
            // Requer SQLite >= 3.25.0
            $sql = "ALTER TABLE \"$current_table\" RENAME COLUMN \"$old_col\" TO \"$new_col\"";
            $pdo->exec($sql);
            header($success_redirect . urlencode("Coluna '$old_col' renomeada para '$new_col' com sucesso."));
            exit;

        } elseif ($_POST['action_type'] === 'drop_column' && isset($_POST['col_to_drop'])) {
            $col_to_drop = trim($_POST['col_to_drop']);

            if (empty($col_to_drop)) {
                throw new Exception("Nome da coluna a deletar não pode ser vazio.");
            }

            // Requer SQLite >= 3.35.0
            $sql = "ALTER TABLE \"$current_table\" DROP COLUMN \"$col_to_drop\"";
            $pdo->exec($sql);
            header($success_redirect . urlencode("Coluna '$col_to_drop' deletada com sucesso."));
            exit;
            
        } elseif ($_POST['action_type'] === 'add_column' && isset($_POST['new_col_name'], $_POST['new_col_type'])) {
            $col_name = trim($_POST['new_col_name']);
            $col_type = trim($_POST['new_col_type']);
            $col_not_null = isset($_POST['col_not_null']); 
            $col_default = trim($_POST['col_default'] ?? '');
            $col_default_str = '';
            $not_null_str = $col_not_null ? ' NOT NULL' : '';

            if ($col_not_null && (empty($col_default) || strtoupper($col_default) === 'NULL')) {
                throw new Exception("Colunas 'NOT NULL' adicionadas DEVEM ter um valor DEFAULT (ex: 0, '', 'N/A').");
            }

            if (!empty($col_default) && strtoupper($col_default) !== 'NULL') {
                $col_default_str = " DEFAULT " . $pdo->quote($col_default);
            }

            if (empty($col_name) || empty($col_type)) {
                throw new Exception("Nome e Tipo da coluna são obrigatórios.");
            }
            
            $sql = "ALTER TABLE \"$current_table\" ADD COLUMN \"$col_name\" $col_type $col_default_str $not_null_str";
            $pdo->exec($sql);
            header($success_redirect . urlencode("Coluna '$col_name' adicionada com sucesso."));
            exit;

        // ---------------------------------------------------------------------------------------------------
        // LÓGICA PRINCIPAL: MODIFICAR TIPO DE COLUNA (RECRIAÇÃO DA TABELA DENTRO DE TRANSACTION)
        // ---------------------------------------------------------------------------------------------------
        } elseif ($_POST['action_type'] === 'change_column_type' && isset($_POST['col_to_modify'], $_POST['new_col_type'])) {
            $old_col_name = trim($_POST['col_to_modify']);
            $new_type = trim($_POST['new_col_type']);
            $old_table_name = "{$current_table}_old"; // Nome temporário da tabela original

            if (empty($old_col_name) || empty($new_type)) {
                throw new Exception("Nome da coluna e novo tipo são obrigatórios.");
            }

            $pdo->beginTransaction();
            try {
                $structure = get_table_structure($pdo, $current_table);
                $new_cols_def = []; 
                $select_cols = [];  

                foreach ($structure as $col) {
                    $name = $col['name'];
                    $type = $col['type'];
                    $pk = $col['pk'];
                    $notnull = $col['notnull'];
                    $dflt_value = $col['dflt_value']; 

                    // Define o tipo e o SELECT CAST/FUNCTION
                    if ($name === $old_col_name) {
                        // Coluna MODIFICADA: usa o novo tipo
                        $col_type_def = $new_type;
                        
                        // LÓGICA DE CONVERSÃO DE TICKS (18 DÍGITOS) PARA DATETIME
                        if (strtoupper($type) === 'INTEGER' && strtoupper($new_type) === 'TEXT') {
                            // Offset em 100-nanoseconds (ticks) de 0001-01-01 até 1970-01-01 (Unix Epoch)
                            $ticks_offset = '621355968000000000'; 
                            // Divisor: 10,000,000 para converter de 100-nanoseconds para segundos
                            $ticks_divisor = '10000000';
                            
                            // A expressão SQL: (Ticks - Offset) / Divisor = Unix Seconds.
                            $select_cols[] = "datetime((\"$name\" - $ticks_offset) / $ticks_divisor, 'unixepoch')";
                            $conversion_message = " (Conversão de Ticks (100ns) para DATETIME aplicada)";

                        } else {
                            // Conversão Padrão: usa CAST para o novo tipo
                            $select_cols[] = "CAST(\"$name\" AS $new_type)";
                            $conversion_message = ""; 
                        }

                    } else {
                        // Coluna NÃO MODIFICADA: mantém o tipo original e copia diretamente
                        $col_type_def = $type;
                        $select_cols[] = "\"$name\"";
                    }

                    // Monta a definição da coluna (Constraints)
                    $col_def = "\"$name\" $col_type_def";
                    
                    if ($pk) {
                        $col_def .= " PRIMARY KEY";
                    }
                    if ($notnull) {
                        $col_def .= " NOT NULL";
                    }
                    if ($dflt_value !== null) {
                        $col_def .= " DEFAULT " . $dflt_value;
                    }

                    $new_cols_def[] = $col_def;
                }
                
                if (empty($new_cols_def)) {
                    throw new Exception("Estrutura da tabela não encontrada.");
                }

                $new_create_sql = "CREATE TABLE \"$current_table\" (" . implode(', ', $new_cols_def) . ")";
                $insert_select_sql = "INSERT INTO \"$current_table\" SELECT " . implode(', ', $select_cols) . " FROM \"$old_table_name\"";

                // PASSO 1: Renomear tabela original para nome temporário
                $pdo->exec("ALTER TABLE \"$current_table\" RENAME TO \"$old_table_name\"");

                // PASSO 2: Criar a nova tabela com o nome original e tipo de coluna alterado
                $pdo->exec($new_create_sql);

                // PASSO 3: Copiar dados da tabela antiga para a nova (com CONVERSÃO)
                $pdo->exec($insert_select_sql);

                // PASSO 4: Deletar a tabela temporária
                $pdo->exec("DROP TABLE \"$old_table_name\"");

                // PASSO 5: Comitar a transação
                $pdo->commit();
                
                // Usa a mensagem de conversão dinâmica
                header($success_redirect . urlencode("Tipo da coluna '$old_col_name' alterado para '$new_type' com sucesso" . $conversion_message));
                exit;

            } catch (Exception $e) {
                // Se algo falhar, reverte a transação
                $pdo->rollBack();
                throw $e; // Relança o erro para ser pego pelo bloco catch externo
            }
        }

    } catch (Exception $e) {
        // Trata erros de qualquer uma das ações POST
        header($error_redirect . urlencode($e->getMessage()));
        exit;
    }
}

// -----------------------------------------------------------------
// RENDERIZAÇÃO
// -----------------------------------------------------------------
$table_structure = get_table_structure($pdo, $current_table);

?>

<div class="row">
    <div class="col-12">
        <h2 class="h4">Estrutura de Colunas</h2>

        <div class="alert alert-info" role="alert">
            <strong>AVISO:</strong> A alteração de tipo de coluna de **INTEGER para TEXT** agora inclui uma verificação para valores grandes (como Ticks do .NET/Windows) e aplica a conversão para **DATETIME** legível.
        </div>
        
        <button type="button" class="btn btn-success btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#addColumnModal"><i class="bi bi-plus-circle"></i> Adicionar Coluna</button>

        <div class="table-responsive mb-4">
            <table class="table table-bordered table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th><th>Tipo Atual</th><th>PK / AI</th><th>NOT NULL</th><th>Valor Padrão</th><th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($table_structure)): ?>
                        <?php foreach ($table_structure as $col): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($col['name']); ?></td>
                                <td>
                                    <span class="text-info"><?php echo htmlspecialchars($col['type']); ?></span>
                                </td>
                                <td><?php echo $col['pk'] ? 'PK' : 'Não'; ?></td>
                                <td><?php echo $col['notnull'] ? 'Sim' : 'Não'; ?></td>
                                <td><?php echo htmlspecialchars($col['dflt_value'] ?? 'NULL'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning me-2 rename-col-btn" data-bs-toggle="modal" data-bs-target="#renameColumnModal" data-old-name="<?php echo htmlspecialchars($col['name']); ?>">
                                        <i class="bi bi-pencil"></i> Renomear
                                    </button>
                                    
                                    <button class="btn btn-sm btn-info me-2 modify-type-btn" data-bs-toggle="modal" data-bs-target="#modifyTypeModal" data-col-name="<?php echo htmlspecialchars($col['name']); ?>" data-col-type="<?php echo htmlspecialchars($col['type']); ?>">
                                        <i class="bi bi-arrow-repeat"></i> Tipo
                                    </button>

                                    <?php if (!$col['pk'] && count($table_structure) > 1): ?>
                                        <button class="btn btn-sm btn-danger delete-col-btn" data-bs-toggle="modal" data-bs-target="#deleteColumnModal" data-col-name="<?php echo htmlspecialchars($col['name']); ?>">
                                            <i class="bi bi-trash"></i> Deletar
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted">Nenhuma coluna encontrada para esta tabela.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="renameColumnModal" tabindex="-1" aria-labelledby="renameColumnModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="table_editor.php?db=<?php echo $db_param; ?>&table=<?php echo $table_param; ?>&module=estrutura">
                <input type="hidden" name="action_type" value="rename_column">
                <input type="hidden" name="old_col_name" id="rename-old-col-name">

                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="renameColumnModalLabel"><i class="bi bi-pencil"></i> Renomear Coluna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Renomeando coluna em **<?php echo htmlspecialchars($current_table); ?>**</p>
                    <div class="mb-3">
                        <label for="rename-new-col-name" class="form-label">Novo Nome da Coluna:</label>
                        <input type="text" name="new_col_name" id="rename-new-col-name" class="form-control" required>
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

<div class="modal fade" id="deleteColumnModal" tabindex="-1" aria-labelledby="deleteColumnModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="table_editor.php?db=<?php echo $db_param; ?>&table=<?php echo $table_param; ?>&module=estrutura">
                <input type="hidden" name="action_type" value="drop_column">
                <input type="hidden" name="col_to_drop" id="delete-col-name">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteColumnModalLabel"><i class="bi bi-trash"></i> Confirmar Exclusão de Coluna</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="lead">Tem certeza que deseja **deletar permanentemente** a coluna: <strong class="text-danger" id="delete-col-display"></strong> da tabela **<?php echo htmlspecialchars($current_table); ?>**?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Sim, Deletar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addColumnModal" tabindex="-1" aria-labelledby="addColumnModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addColumnModalLabel"><i class="bi bi-plus-circle"></i> Adicionar Nova Coluna em **<?php echo htmlspecialchars($current_table); ?>**</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="table_editor.php?db=<?php echo $db_param; ?>&table=<?php echo $table_param; ?>&module=estrutura">
                <div class="modal-body">
                    <input type="hidden" name="action_type" value="add_column">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="new_col_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="new_col_type" class="form-select">
                            <option value="TEXT" selected>TEXT</option>
                            <option value="INTEGER">INTEGER</option>
                            <option value="REAL">REAL</option>
                            <option value="BLOB">BLOB</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor Padrão (Opcional)</label>
                        <input type="text" name="col_default" class="form-control" placeholder="Ex: 'valor', 0, NULL">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="col_not_null_check_add" name="col_not_null">
                        <label class="form-check-label" for="col_not_null_check_add">
                            NOT NULL (Se marcado, o valor padrão DEVE ser fornecido)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle"></i> Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modifyTypeModal" tabindex="-1" aria-labelledby="modifyTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="table_editor.php?db=<?php echo $db_param; ?>&table=<?php echo $table_param; ?>&module=estrutura">
                <input type="hidden" name="action_type" value="change_column_type">
                <input type="hidden" name="col_to_modify" id="modify-col-name">

                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modifyTypeModalLabel"><i class="bi bi-arrow-repeat"></i> Mudar Tipo da Coluna: <span id="modify-col-display"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tipo Atual: <strong id="modify-current-type" class="text-warning"></strong></p>
                    <div class="alert alert-danger" role="alert">
                        <strong>RISCO DE DADOS:</strong> A conversão de dados pode causar perda de precisão ou erros (ex: texto 'ABC' para INTEGER). **Back-up é recomendado!**
                        <br>Se for de **INTEGER para TEXT**, o sistema aplicará a conversão de **Ticks (100ns) para DATETIME**.
                    </div>
                    <div class="mb-3">
                        <label for="modify-new-col-type" class="form-label">Novo Tipo de Dado:</label>
                        <select name="new_col_type" id="modify-new-col-type" class="form-select" required>
                            <option value="TEXT">TEXT</option>
                            <option value="INTEGER">INTEGER</option>
                            <option value="REAL">REAL</option>
                            <option value="BLOB">BLOB</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info text-white"><i class="bi bi-save"></i> Mudar Tipo (Transação)</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    // Scripts para preencher os modais Renomear, Deletar e Modificar Tipo
    document.addEventListener('DOMContentLoaded', function () {
        // Renomear Coluna
        $('#renameColumnModal').on('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const oldName = button.getAttribute('data-old-name');
            document.getElementById('rename-old-col-name').value = oldName;
            document.getElementById('rename-new-col-name').value = oldName; 
            setTimeout(() => document.getElementById('rename-new-col-name').select(), 100);
        });
        
        // Deletar Coluna
        $('#deleteColumnModal').on('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const colName = button.getAttribute('data-col-name');
            document.getElementById('delete-col-name').value = colName;
            document.getElementById('delete-col-display').textContent = colName;
        });

        // Modificar Tipo de Coluna
        $('#modifyTypeModal').on('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const colName = button.getAttribute('data-col-name');
            const colType = button.getAttribute('data-col-type');

            document.getElementById('modify-col-name').value = colName;
            document.getElementById('modify-col-display').textContent = colName;
            document.getElementById('modify-current-type').textContent = colType;

            // Seleciona o tipo de dado atual no dropdown como valor padrão
            document.getElementById('modify-new-col-type').value = colType;
        });
    });
</script>