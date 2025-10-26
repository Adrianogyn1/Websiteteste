<?php include __DIR__.'/includes/header.php'; ?>


<main class="container-fluid pt-5 mt-3">
    <div class="container py-4">

        <!-- ===== TÍTULO ===== -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">
                <span class="material-symbols-outlined text-primary align-middle">settings</span>
                Configurações
            </h3>
        </div>

        <!-- ===== SEÇÃO: GERAIS ===== -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-dark text-light">
                <span class="material-symbols-outlined align-middle me-1">tune</span>
                Preferências Gerais
            </div>
            <div class="card-body">
                <form id="formConfigGeral">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tema</label>
                            <select class="form-select" name="tema">
                                <option value="claro">Claro</option>
                                <option value="escuro">Escuro</option>
                                <option value="auto">Automático</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Idioma</label>
                            <select class="form-select" name="idioma">
                                <option value="pt-BR">Português (Brasil)</option>
                                <option value="en-US">Inglês</option>
                                <option value="es-ES">Espanhol</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" id="notificacoes" name="notificacoes" checked>
                                <label class="form-check-label" for="notificacoes">Ativar notificações do sistema</label>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <span class="material-symbols-outlined align-middle me-1">save</span>
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===== SEÇÃO: SISTEMA ===== -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-dark text-light">
                <span class="material-symbols-outlined align-middle me-1">computer</span>
                Configurações do Sistema
            </div>
            <div class="card-body">
                <form id="formSistema">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Versão do Sistema</label>
                            <input type="text" class="form-control" name="versao" value="1.0.0" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Última Atualização</label>
                            <input type="text" class="form-control" name="ultimaAtualizacao" value="<?= date('d/m/Y') ?>" readonly>
                        </div>

                        <div class="col-md-12">
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="autoUpdate" id="autoUpdate">
                                <label class="form-check-label" for="autoUpdate">Permitir atualizações automáticas</label>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-outline-primary px-4">
                            <span class="material-symbols-outlined align-middle me-1">system_update</span>
                            Atualizar Sistema
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===== SEÇÃO: SEGURANÇA ===== -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-light">
                <span class="material-symbols-outlined align-middle me-1">lock</span>
                Segurança
            </div>
            <div class="card-body">
                <form id="formSeguranca">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tempo de sessão (minutos)</label>
                            <input type="number" class="form-control" name="tempoSessao" value="30" min="5">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tentativas máximas de login</label>
                            <input type="number" class="form-control" name="tentativas" value="3" min="1">
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-danger px-4">
                            <span class="material-symbols-outlined align-middle me-1">security_update_good</span>
                            Aplicar Segurança
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</main>

<script>
$(function() {
    // Simulação de salvamento local (mock)
    $('#formConfigGeral, #formSistema, #formSeguranca').on('submit', function(e) {
        e.preventDefault();
        alert('Configurações salvas com sucesso!');
        // Aqui poderia ir um $.post('api/config/salvar.php', $(this).serialize())
    });
    
     $('#formSistema').on('submit', function(e) {
        e.preventDefault();

            
            $.post('/git.php', {atualizar: true}, function(data){
        alert('atualizado');
    });
    
    
});


</script>

<?php include __DIR__.'/includes/footer.php'; ?>




