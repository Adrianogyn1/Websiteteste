<div class="modal fade" id="modalDeposito" tabindex="-1" aria-labelledby="modalDepositoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title" id="modalDepositoLabel">Editar Depósito (Recorrente/Detalhes)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="form-deposito-edit">
                    <p class="text-muted">Ajuste os detalhes do seu último depósito</p>
                    
                    
                    
                    <div class="mb-3">
                        <label for="depositoValor" class="form-label">Valor do Depósito</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" id="depositoValor" min="10" value="100.00" required>
                        </div>
                    </div>

                    
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="form-deposito-edit" class="btn btn-info text-dark">Salvar Configurações</button>
            </div>
        </div>
    </div>
</div>