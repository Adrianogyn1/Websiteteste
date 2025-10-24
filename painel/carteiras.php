<?php include __DIR__.'/includes/header.php'; ?>


<main class="container-fluid pt-5 mt-3">
    <div class="container py-4">

        <!-- ===== TÍTULO E AÇÃO ===== -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">
                <span class="material-symbols-outlined align-middle text-primary">account_balance_wallet</span>
                Minhas Carteiras
            </h3>
            <button class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#novaCarteiraModal">
                <span class="material-symbols-outlined me-1">add</span> Nova Carteira
            </button>
        </div>

        <!-- ===== TABELA DE CARTEIRAS ===== -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Nome</th>
                                <th scope="col">Saldo</th>
                                <th scope="col">Tipo</th>
                                <th scope="col">Atualizado em</th>
                                <th scope="col" class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaCarteiras">
                            <tr>
                                <td>1</td>
                                <td>Carteira Principal</td>
                                <td><span class="text-success fw-bold">R$ 2.350,00</span></td>
                                <td><span class="badge bg-primary">Apostas</span></td>
                                <td>23/10/2025 14:32</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" title="Excluir">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Carteira Secundária</td>
                                <td><span class="text-success fw-bold">R$ 780,00</span></td>
                                <td><span class="badge bg-success">Cassino</span></td>
                                <td>20/10/2025 10:18</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" title="Excluir">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ===== MODAL NOVA CARTEIRA ===== -->
        <div class="modal fade" id="novaCarteiraModal" tabindex="-1" aria-labelledby="novaCarteiraLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-dark text-light">
                        <h5 class="modal-title" id="novaCarteiraLabel">
                            <span class="material-symbols-outlined align-middle">add_circle</span>
                            Nova Carteira
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="formNovaCarteira" method="POST">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="nomeCarteira" class="form-label">Nome da Carteira</label>
                                <input type="text" class="form-control" id="nomeCarteira" name="nome" required>
                            </div>
                            <div class="mb-3">
                                <label for="tipoCarteira" class="form-label">Tipo</label>
                                <select id="tipoCarteira" name="tipo" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <option value="apostas">Apostas</option>
                                    <option value="cassino">Cassino</option>
                                    <option value="esportes">Esportes</option>
                                    <option value="outros">Outros</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="saldoInicial" class="form-label">Saldo Inicial</label>
                                <input type="number" class="form-control" id="saldoInicial" name="saldo" step="0.01" placeholder="0.00" required>
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

    </div>
</main>

<script>
    // Exemplo de interação com o formulário
    $('#formNovaCarteira').on('submit', function (e) {
        e.preventDefault();
        const nome = $('#nomeCarteira').val();
        const tipo = $('#tipoCarteira').val();
        const saldo = parseFloat($('#saldoInicial').val()).toFixed(2);

        if (nome && tipo && saldo >= 0) {
            const novaLinha = `
                <tr>
                    <td>-</td>
                    <td>${nome}</td>
                    <td><span class="text-success fw-bold">R$ ${saldo}</span></td>
                    <td><span class="badge bg-info text-dark">${tipo}</span></td>
                    <td>${new Date().toLocaleString()}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-secondary" title="Editar">
                            <span class="material-symbols-outlined">edit</span>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" title="Excluir">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </td>
                </tr>`;
            $('#tabelaCarteiras').append(novaLinha);
            $('#novaCarteiraModal').modal('hide');
            this.reset();
        }
    });
</script>

<?php include __DIR__.'/includes/footer.php'; ?>





