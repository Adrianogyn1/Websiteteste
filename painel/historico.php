<?php include 'header.php'; ?>

<main class="container-fluid pt-5 mt-3">
    <div class="container py-4">

        <!-- ===== CABEÇALHO ===== -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">
                <span class="material-symbols-outlined text-primary align-middle">history</span>
                Histórico
            </h3>

            <!-- FILTRO DE PERÍODO -->
            <div class="input-group" style="max-width: 280px;">
                <span class="input-group-text bg-dark text-light border-0">
                    <span class="material-symbols-outlined">date_range</span>
                </span>
                <input type="text" id="filtroData" class="form-control" placeholder="Selecione o período">
            </div>
        </div>

        <!-- ===== RESUMO ===== -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <span class="material-symbols-outlined text-success" style="font-size: 36px;">arrow_downward</span>
                        <h6 class="text-muted">Entradas</h6>
                        <h4 class="fw-bold text-success" id="totalEntradas">R$ 0,00</h4>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <span class="material-symbols-outlined text-danger" style="font-size: 36px;">arrow_upward</span>
                        <h6 class="text-muted">Saídas</h6>
                        <h4 class="fw-bold text-danger" id="totalSaidas">R$ 0,00</h4>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <span class="material-symbols-outlined text-primary" style="font-size: 36px;">account_balance_wallet</span>
                        <h6 class="text-muted">Saldo Final</h6>
                        <h4 class="fw-bold text-primary" id="saldoFinal">R$ 0,00</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== TABELA ===== -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-light d-flex align-items-center justify-content-between">
                <div>
                    <span class="material-symbols-outlined align-middle me-1">list_alt</span>
                    Histórico de Transações
                </div>
                <button class="btn btn-sm btn-outline-light" id="btnRecarregar">
                    <span class="material-symbols-outlined align-middle me-1">refresh</span>
                    Atualizar
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle" id="tabelaHistorico">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th class="text-end">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Linhas preenchidas via JS -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<script>
$(function() {
    // ======== DATERANGEPICKER ========
    $('#filtroData').daterangepicker({
        locale: { format: 'DD/MM/YYYY', applyLabel: 'Aplicar', cancelLabel: 'Cancelar' },
        opens: 'left'
    });

    // ======== MOCK DE DADOS ========
    const historico = [
        { id: 1, data: '20/10/2025', tipo: 'Entrada', desc: 'Depósito inicial', valor: 500 },
        { id: 2, data: '21/10/2025', tipo: 'Saída', desc: 'Aposta em jogo 123', valor: -50 },
        { id: 3, data: '22/10/2025', tipo: 'Entrada', desc: 'Premiação', valor: 120 },
        { id: 4, data: '23/10/2025', tipo: 'Saída', desc: 'Retirada', valor: -100 },
    ];

    function carregarTabela() {
        const tbody = $('#tabelaHistorico tbody');
        tbody.empty();
        let totalEntradas = 0, totalSaidas = 0;

        historico.forEach(item => {
            const valor = item.valor;
            const tipo = valor > 0 ? 'Entrada' : 'Saída';
            const cor = valor > 0 ? 'text-success' : 'text-danger';
            const sinal = valor > 0 ? '+' : '';

            if (valor > 0) totalEntradas += valor;
            else totalSaidas += Math.abs(valor);

            tbody.append(`
                <tr>
                    <td>${item.id}</td>
                    <td>${item.data}</td>
                    <td>${tipo}</td>
                    <td>${item.desc}</td>
                    <td class="text-end ${cor} fw-bold">${sinal}R$ ${Math.abs(valor).toFixed(2)}</td>
                </tr>
            `);
        });

        const saldo = totalEntradas - totalSaidas;
        $('#totalEntradas').text('R$ ' + totalEntradas.toFixed(2));
        $('#totalSaidas').text('R$ ' + totalSaidas.toFixed(2));
        $('#saldoFinal').text('R$ ' + saldo.toFixed(2));
    }

    carregarTabela();

    // ======== BOTÃO RECARREGAR ========
    $('#btnRecarregar').click(() => {
        carregarTabela();
        alert('Histórico atualizado!');
    });
});
</script>

<?php include 'footer.php'; ?>
