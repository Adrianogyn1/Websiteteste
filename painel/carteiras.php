<?php include __DIR__.'/includes/header.php'; ?>

<main class="container-fluid pt-5 mt-3">
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">
                <span class="material-symbols-outlined align-middle text-primary">account_balance_wallet</span>
                Minhas Carteiras
            </h3>
            <button class="btn btn-primary d-flex align-items-center" id="btn-add-carteira">
                <span class="material-symbols-outlined me-1">add</span> Nova Carteira
            </button>
        </div>

        <div class="row g-3" id="carteirasList">
            </div>

        <div id="modal-container"></div>
        
    </div>
</main>

<script>
const MAX_CARTEIRAS = 10;
const MODAL_ID = '#modalEdit';
const FORM_ID = '#formNovaCarteira';
const API_URL = '/app/api/carteira/';

// ==========================================================
// FUNÇÃO PRINCIPAL PARA CARREGAR A LISTA
// ==========================================================
function loadCarteiras() {
    $.getJSON(API_URL + 'list.php', function(resp){
        if(!resp.sucess) {
            $('#carteirasList').html('<p class="text-danger">'+resp.msg+'</p>');
            return;
        }

        // Simulação de como você está tratando a resposta da API
        const data = resp.data.data ? resp.data.data.slice(0, MAX_CARTEIRAS) : [];
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
                            <button class="btn btn-sm btn-outline-info btn-select" title="Selecionar"><span class="material-symbols-outlined">select</span></button>
                            <button class="btn btn-sm btn-outline-danger btn-delete" title="Excluir"><span class="material-symbols-outlined">delete</span></button>
                        </div>
                    </div>
                </div>
            </div>`;
            $('#carteirasList').append(card);
        });
    });
}

// ==========================================================
// FUNÇÕES DE AÇÃO DO MODAL
// ==========================================================

// Função para carregar o modal de adição/edição
function loadModalCarteira(id) {
    
    // 1. Limpa o DOM de qualquer modal anterior
    $('#modal-container').empty(); 
    $('.modal-backdrop').remove(); // Garante a remoção do fundo escuro
    
    // 2. Carrega o novo conteúdo do modal (que irá para #modal-container)
    $.ajax({
        url: '/app/painel/modals/editCarteira.php?id=' + id,
        method: 'GET',
        success: function(htmlDoModal) {
           
           // 3. Insere o modal no container
           $('#modal-container').html(htmlDoModal);
           
           // 4. Exibe o modal
           $(MODAL_ID).modal('show');
        },
        error: function(xhr, status, error) {
            alert("Erro ao carregar modal. Verifique o arquivo editCarteira.php");
        }
    });
}

// Função para salvar os dados via AJAX
function saveCarteira() {
    
    const form = $(FORM_ID);
    const formDataArray = form.serializeArray();
    let formData = {};
    
    $(formDataArray).each(function(i, field){
        formData[field.name] = field.value;
    });

    // Tratamento do checkbox (que só envia se estiver marcado)
    if (formData['useRelatorio'] === undefined) {
         formData['useRelatorio'] = 0;
    } else {
         formData['useRelatorio'] = 1;
    }

    // Validação básica
    if(!formData.nome) {
        alert("O nome da carteira é obrigatório.");
        return;
    }

    $.ajax({
        url: API_URL + 'save.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        success: function(resp) {
            
            if(resp.sucess){
                alert(resp.msg);
            
                // Fecha o modal
                $(MODAL_ID).modal('hide');
                
                // Recarrega a lista
                loadCarteiras();

            } else {
                alert('Erro ao salvar: ' + resp.msg);
            }
        },
        error: function(xhr, status, error) {
             alert('Erro na comunicação com o servidor: ' + error);
        }
    });
}


// ==========================================================
// EVENT HANDLERS (Onde o usuário clica)
// ==========================================================

// 1. ABRIR MODAL: Adicionar Nova Carteira (ID 0)
$('#btn-add-carteira').on('click', function(){
    loadModalCarteira(0);
});

// 2. ABRIR MODAL: Editar Carteira (Pega o ID do card)
$('#carteirasList').on('click', '.btn-edit', function(){
    const id = $(this).closest('[data-id]').data('id');
    loadModalCarteira(id);
});

// 3. SALVAR: Submissão do Formulário Carregado Dinamicamente
$('body').on('submit', FORM_ID, function (e) {
    e.preventDefault();
    saveCarteira();
});

// 4. FECHAR: Remoção do Modal e Lixo do DOM (Após animação)
$('body').on('hidden.bs.modal', MODAL_ID, function () {
    // Remove o modal e o fundo do DOM para que ele seja carregado "limpo" novamente
    $(this).remove();
    $('.modal-backdrop').remove(); 
});


// Eventos de Excluir e Selecionar (Mantidos)
$('#carteirasList').on('click', '.btn-delete', function(){
    if(!confirm('Deseja realmente excluir esta carteira?')) return;
    const id = $(this).closest('[data-id]').data('id');
    $.ajax({
        url: API_URL + 'delete.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ id }),
        success: function(resp){
            alert(resp.msg);
            loadCarteiras();
        }
    });
});

$('#carteirasList').on('click', '.btn-select', function(){
    const id = $(this).closest('[data-id]').data('id');
    $.ajax({
        url: API_URL + 'setCarteira.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ id }),
        success: function(resp) {
            alert(resp.msg);
            loadCarteiras();
        }
    });
});


// Inicialização
$(function(){ 
    loadCarteiras(); 
});
</script>

<?php include __DIR__.'/includes/footer.php'; ?>