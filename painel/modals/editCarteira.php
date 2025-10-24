    
    <?php
    require_once(dirname(__DIR__, 1) . '/autoload.php');
session_start();

    $id=2;
    $carteira = (new Carteira())->read($id);
    ?>
    
    
     <div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="novaCarteiraLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-dark text-light">
                        <h5 class="modal-title" id="novaCarteiraLabel">
                            <span class="material-symbols-outlined align-middle">add_circle</span>
                            Carteira
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="formNovaCarteira" method="POST">
                        <input type="hidden" id="carteiraId">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="nomeCarteira" class="form-label">Nome da Carteira</label>
                                <input type="text" value="<?php $carteira->nome; ?>" class="form-control" id="nomeCarteira" name="nome" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="carteiraUrl" class="form-label">Url</label>
                                <input type="text" class="form-control" id="carteiraUrl" name="url" value="<?php $carteira->url; ?> >
                            </div>
                            
                            <div class="mb-3">
                                <label for="carteiraLogin" class="form-label">Login</label>
                                <input type="text" class="form-control" id="carteiraLogin" name="login" value="<?php $carteira->login; ?> >
                            </div>
                            
                           <div class="mb-3">
                                <label for="carteiraRelatorio" class="form-label">Use Relatório</label>
                                <input type="checkbox" class="form-control" id="carteiraRelatorio" name="useRelatorio" >
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