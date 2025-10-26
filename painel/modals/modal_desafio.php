<div class="modal fade" id="modalDesafio" tabindex="-1" aria-labelledby="modalDesafioLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="modalDesafioLabel">Editar/Configurar Desafio de Cassino</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="form-desafio-edit">
                    <p class="text-muted">Defina o tipo de desafio e as regras de participação para o próximo evento.</p>
                    
                    <div class="mb-3">
                        <label for="desafioTipo" class="form-label">Tipo de Desafio</label>
                        <select class="form-select" id="desafioTipo" required>
                            <option value="multiplicador" selected>Maior Multiplicador</option>
                            <option value="apostas">Mais Apostas</option>
                            <option value="sorteio">Sorteio Diário</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="desafioJogo" class="form-label">Jogo Válido para o Desafio</label>
                        <input type="text" class="form-control" id="desafioJogo" value="Mines" placeholder="Ex: Roleta, Plinko, Slots..." required>
                    </div>

                    <div class="mb-3">
                        <label for="desafioPremio" class="form-label">Prêmio Base</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" id="desafioPremio" min="10" value="500.00" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="form-desafio-edit" class="btn btn-warning text-dark">Salvar Regras do Desafio</button>
            </div>
        </div>
    </div>
</div>