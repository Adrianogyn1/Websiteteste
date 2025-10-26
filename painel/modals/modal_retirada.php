<div class="modal fade" id="modalRetirada" tabindex="-1" aria-labelledby="modalRetiradaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalRetiradaLabel">Editar Retirada (Dados de Saque)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="form-retirada-edit">
                    <p class="text-muted">Retirada</p>
                    
                    <?php
                    /*
                    <div class="mb-3">
                        <label for="retiradaMetodo" class="form-label">Método de Saque</label>
                        <select class="form-select" id="retiradaMetodo" required>
                            <option value="pix" selected>Pix</option>
                            <option value="ted">TED (Transferência)</option>
                            <option value="crypto">Criptomoeda</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="retiradaChave" class="form-label">Chave Pix / Identificador de Conta</label>
                        <input type="text" class="form-control" id="retiradaChave" value="123.456.789-00" placeholder="Seu CPF, Email ou Chave Aleatória" required>
                    </div>
                    */
                    ?>

                    <div class="mb-3">
                        <label for="retiradaValorPadrao" class="form-label">Valor  de Retirada</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" id="retiradaValorPadrao" min=".01" value="100000.00" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="form-retirada-edit" class="btn btn-danger">Salvar Dados de Saque</button>
            </div>
        </div>
    </div>
</div>