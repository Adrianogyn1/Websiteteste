<?php
// /app/painel/modals/editCarteira.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Caminho ajustado para o seu autoloader
require_once(dirname(__DIR__, 3) . '/autoload.php'); 
session_start();

$id = intval($_GET['id'] ?? 0);



$carteira = new Carteira();
if ($id) {
    $carteira->read($id);
}

// Determina se o checkbox deve vir marcado
$checked = ($carteira->useRelatorio) ? 'checked' : '';
$titulo = $id ? 'Editar Carteira' : 'Nova Carteira';

?>


<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="novaCarteiraLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
           
            <div class="modal-header bg-dark text-light">
                <h5 class="modal-title" id="novaCarteiraLabel">
                    <span class="material-symbols-outlined align-middle">add_circle</span>
                    <?php echo $titulo; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="formNovaCarteira" method="POST">
                
                <input type="hidden" name="id" id="carteiraId" value="<?php echo $carteira->id; ?>">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nomeCarteira" class="form-label">Nome da Carteira</label>
                        <input type="text" value="<?php echo htmlspecialchars($carteira->nome); ?>" class="form-control" id="nomeCarteira" name="nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="carteiraUrl" class="form-label">Url</label>
                        <input type="text" class="form-control" id="carteiraUrl" name="url" value="<?php echo htmlspecialchars($carteira->url); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="carteiraLogin" class="form-label">Login</label>
                        <input type="text" class="form-control" id="carteiraLogin" name="login" value="<?php echo htmlspecialchars($carteira->login); ?>">
                    </div>
                    
                   <div class="mb-3 form-check">
                       <input type="checkbox" class="form-check-input" id="carteiraRelatorio" name="useRelatorio" value="1" <?php echo $checked; ?>>
                        <label for="carteiraRelatorio" class="form-check-label">Use Relatório</label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>