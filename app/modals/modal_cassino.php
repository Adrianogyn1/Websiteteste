<div class="modal fade" id="modalCassino" tabindex="-1" aria-labelledby="modalCassinoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalCassinoLabel">Editar Aposta em Cassino</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="form-cassino-edit">
                    <div class="mb-3">
                        <label for="cassinoJogo" class="form-label">Jogo</label>
                        <input type="text" class="form-control" id="cassinoJogo" value="Roleta VIP" required>
                    </div>
                    <div class="mb-3">
                        <label for="cassinoValor" class="form-label">Valor da Aposta</label>
                        <input type="number" class="form-control" id="cassinoValor" value="50.00" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="cassinoDuplicar" checked>
                        <label class="form-check-label" for="cassinoDuplicar">Duplicar Aposta?</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="form-cassino-edit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>