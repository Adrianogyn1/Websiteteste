<style>
    /* Container Principal: Alinha os itens horizontalmente */
    .action-menu {
        display: flex;
        flex-direction: row; 
        flex-wrap: wrap;
        justify-content: center;
        
        /* Estilos do Bloco */
        max-width: fit-content; 
        margin: 15px auto; /* Espaçamento ajustado para caber no layout */
        padding: 10px;
        
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    /* Espaçamento entre os ícones */
    .action-menu a {
        margin: 0 5px; 
    }

    /* Oculta o título */
    .action-menu h3 {
        display: none;
    }

    /* Estilo dos Botões (Ícones) */
    .action-menu .btn {
        width: 45px;
        height: 45px;
        padding: 8px;
        display: flex; 
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 1.1rem; 
    }
</style>

<div class="action-menu-wrapper">
    <div class="action-menu">
        <h3 class="text-center mb-4">Menu de Ações</h3>

        <a href="#" 
           class="btn btn-primary load-modal-btn" 
           data-bs-toggle="tooltip" 
           data-bs-placement="bottom" 
           title="Editar Aposta Cassino" 
           data-bs-target="#modalCassino"
           data-modal-url="modal_cassino.php">
            <span class="material-symbols-outlined">casino</span> 
        </a>

        <a href="#" 
           class="btn btn-success load-modal-btn" 
           data-bs-toggle="tooltip" 
           data-bs-placement="bottom" 
           title="Editar Aposta Esportes" 
           data-bs-target="#modalEsportes"
           data-modal-url="modal_esportes.php">
            <span class="material-symbols-outlined">sports_soccer</span> 
        </a>

        <a href="#" 
           class="btn btn-warning text-dark load-modal-btn" 
           data-bs-toggle="tooltip" 
           data-bs-placement="bottom" 
           title="Editar Desafio" 
           data-bs-target="#modalDesafio"
           data-modal-url="modal_desafio.php">
            <span class="material-symbols-outlined">star</span> 
        </a>

        <a href="#" 
           class="btn btn-info text-dark load-modal-btn" 
           data-bs-toggle="tooltip" 
           data-bs-placement="bottom" 
           title="Editar Depósito" 
           data-bs-target="#modalDeposito"
           data-modal-url="modal_deposito.php">
            <span class="material-symbols-outlined">account_balance</span> 
        </a>

        <a href="#" 
           class="btn btn-danger load-modal-btn" 
           data-bs-toggle="tooltip" 
           data-bs-placement="bottom" 
           title="Editar Retirada" 
           data-bs-target="#modalRetirada"
           data-modal-url="modal_retirada.php">
            <span class="material-symbols-outlined">payments</span> 
        </a>
    </div>
</div>

<div id="dynamic-modal-container"></div>


<script>
    $(document).ready(function () {
        const $modalContainer = $('#dynamic-modal-container');
        const $loadModalButtons = $('.load-modal-btn');
        
        // 1. Inicialização do Tooltip
        $('[data-bs-toggle="tooltip"]').tooltip();

        // 2. Lógica para carregar o Modal dinamicamente
        $loadModalButtons.on('click', function (event) {
            event.preventDefault(); 

            const $button = $(this);
            const modalId = $button.data('bs-target'); 
            const modalUrl = "/app/painel/modals/"+$button.data('modal-url'); 

            // Limpa o conteúdo anterior
            $modalContainer.empty(); 
            
            // Requisita o arquivo HTML do modal
            $.get(modalUrl)
                .done(function (htmlContent) {
                    // Injeta o HTML no container
                    $modalContainer.html(htmlContent);
                    
                    // Inicializa e mostra o modal do Bootstrap
                    const $newModalElement = $(modalId); 
                    const modalInstance = new bootstrap.Modal($newModalElement[0]); 
                    modalInstance.show();
                })
                .fail(function (jqXHR, textStatus, errorThrown) {
                    console.error(`Falha ao carregar o modal: ${textStatus}`, errorThrown);
                    alert(`Erro ao carregar a função. Verifique se o arquivo "${modalUrl}" existe.`);
                });
        });
    });
</script>