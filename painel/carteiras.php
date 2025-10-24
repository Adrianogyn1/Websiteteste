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

        <!-- ===== CARDS DE CARTEIRAS ===== -->
        <div class="row g-3" id="carteirasList">
            <!-- Cards carregados via JS -->
        </div>

        <!-- ===== MODAL NOVA/EDITAR CARTEIRA ===== -->
        <div class="modal fade" id="novaCarteiraModal" tabindex="-1" aria-labelledby="novaCarteiraLabel" aria-hidden="true">
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
                                <input type="text" class="form-control" id="nomeCarteira" name="nome" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="carteiraUrl" class="form-label">Url</label>
                                <input type="text" class="form-control" id="carteiraUrl" name="url" >
                            </div>
                            
                            <div class="mb-3">
                                <label for="carteiraLogin" class="form-label">Login</label>
                                <input type="text" class="form-control" id="carteiraLogin" name="login" >
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

    </div>
</main>

<script>
const MAX_CARTEIRAS = 10;

// Carregar carteiras
function loadCarteiras() {
    $.getJSON('/app/api/carteira/list.php', function(resp){
        if(!resp.sucess) {
            $('#carteirasList').html('<p class="text-danger">'+resp.msg+'</p>');
            return;
        }

        const data = resp.data.data.slice(0, MAX_CARTEIRAS);
        $('#carteirasList').empty();

        if(data.length === 0){
            $('#carteirasList').html('<p>Nenhuma carteira cadastrada.</p>');
            return;
        }

        data.forEach(c => {
            const card = `
            <div class="col-md-4" data-id="${c.id}">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title">${c.nome}</h5>
                            <p class="card-text mb-1"><strong>Saldo:</strong> <span class="text-success fw-bold">R$ ${parseFloat(c.saldo).toFixed(2)}</span></p>
                            <p class="card-text mb-1"><strong>Tipo:</strong> <span class="badge bg-info text-dark">${c.tipo}</span></p>
                            <p class="card-text text-muted"><small>Atualizado em ${c.update_at}</small></p>
                        </div>
                        <div class="mt-3 d-flex justify-content-between">
                            <button class="btn btn-sm btn-outline-secondary btn-edit" title="Editar"><span class="material-symbols-outlined">edit</span></button>
                                 <button class="btn btn-sm btn-outline-info btn-select" title="Editar"><span class="material-symbols-outlined">select</span></button>
                            <button class="btn btn-sm btn-outline-danger btn-delete" title="Excluir"><span class="material-symbols-outlined">delete</span></button>
                        </div>
                    </div>
                </div>
            </div>`;
            $('#carteirasList').append(card);
        });
    });
}

// Criar ou atualizar carteira
$('#formNovaCarteira').on('submit', function(e){
    e.preventDefault();
    const id = $('#carteiraId').val() || 0;
    const nome = $('#nomeCarteira').val();
   // const tipo = $('#tipoCarteira').val();
   // const saldo = parseFloat($('#saldoInicial').val()).toFixed(2);

    if(!nome ) return;

    $.ajax({
        url: '/app/api/carteira/save.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ id, nome }),
        success: function(resp)
        {
            
            if(resp.sucess){
                alert(resp.msg);
            $('#novaCarteiraModal').modal('hide');
            $('#formNovaCarteira')[0].reset();
            $('#carteiraId').val('');
            loadCarteiras();
            }else{
                alert(resp.msg);
            }
        }
    });
});

// Editar carteira
$('#carteirasList').on('click', '.btn-edit', function(){
    const card = $(this).closest('[data-id]');
    const id = card.data('id');

    $.getJSON(`/app/api/carteira/get.php?id=${id}`, function(resp){
        if(!resp.sucess) return alert(resp.msg);

        const c = resp.data;
        $('#carteiraId').val(c.id);
        $('#nomeCarteira').val(c.nome);
     //   $('#carteiraUrl').val(c.url);
       // $('#carteiraLogin').val(c.login);
        $('#novaCarteiraModal').modal('show');
    });
});

// Excluir carteira
$('#carteirasList').on('click', '.btn-delete', function(){
    if(!confirm('Deseja realmente excluir esta carteira?')) return;

    const card = $(this).closest('[data-id]');
    const id = card.data('id');

    $.ajax({
        url: '/app/api/carteira/delete.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ id }),
        success: function(resp){
            alert(resp.msg);
            loadCarteiras();
        }
    });
});

// Inicial
$(function(){ loadCarteiras(); });
</script>

<?php include __DIR__.'/includes/footer.php'; ?>
