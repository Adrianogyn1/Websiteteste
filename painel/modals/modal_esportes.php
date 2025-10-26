<div class="modal fade" id="modalEsportes" tabindex="-1" aria-labelledby="modalEsportesLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalEsportesLabel">Editar Configurações de Aposta Esportiva</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="form-esportes-edit">
                    <p class="text-muted">Ajuste seu valor de aposta padrão e as preferências de notificação para seus esportes favoritos.</p>
                    
                    <div class="mb-3">
                        <label for="esporteFavorito" class="form-label">Esporte Favorito</label>
                        <select class="form-select" id="esporteFavorito" required>
                            <option value="futebol" selected>Futebol</option>
                            <option value="basquete">Basquete</option>
                            <option value="tenis">Tênis</option>
                            <option value="mma">MMA</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="esporteValorPadrao" class="form-label">Valor Padrão da Aposta</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" id="esporteValorPadrao" min="1" value="25.00" required>
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="esporteNotificacao" checked>
                        <label class="form-check-label" for="esporteNotificacao">Receber alertas de Odds aumentadas</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="form-esportes-edit" class="btn btn-success">Salvar Configurações</button>
            </div>
        </div>
    </div>
</div>