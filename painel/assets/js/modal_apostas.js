$(document).ready(function () 
{
    // --- VARIÁVEIS DE CONFIGURAÇÃO ---
    const API_ENDPOINT_DEPOSITO = '/app/api/pay/save.php';
    const API_ENDPOINT_RETIRADA = '/app/api/pay/save.php';
    const API_ENDPOINT_CASSINO = '/app/api/pay/save.php';
    
    const $modalContainer = $('#dynamic-modal-container');
    const $loadModalButtons = $('.load-modal-btn');
    
    // ------------------------------------
    // 1. INICIALIZAÇÃO E CARREGAMENTO DINÂMICO DOS MODAIS
    // ------------------------------------

    // Inicialização do Tooltip
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Lógica para carregar o Modal dinamicamente usando $.get
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

    // ------------------------------------
    // 2. SUBMISSÃO DE FORMULÁRIOS (Requisições AJAX POST)
    // Usando delegação para garantir que funciona com modais dinâmicos
    // ------------------------------------

    // --- APOSTA EM CASSINO ---
    $(document).on('submit', '#form-cassino-edit', function(event) {
        event.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');

        $submitButton.prop('disabled', true).text('Apostando...');

        const valorAposta = parseFloat($form.find('input[name="valor"]').val()) || 0;
        const gameId = parseInt($form.find('input[name="gameId"]').val()) || 0;
        
        const formData = {
            playerId: 123, 
            carteiraId: 5, 
            valor: valorAposta, 
            gameId: gameId,
            gameNome: $form.find('input[name="jogoNome"]').val(),
            type: 3, //  TransasaoType::Aposta
            
            // Valores de Exemplo para a classe PaymanetHistorico
            
            tempo: 0, 
            duplicar: $form.find('input[name="duplicar"]').is(':checked')
        };

        $.ajax({
            url: API_ENDPOINT_CASSINO,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                alert('Aposta registrada! Fechando modal...');
                $('#modalCassino').modal('hide'); 
            },
            error: function(xhr) {
                console.error('Erro no AJAX (Cassino):', xhr.responseText);
                alert('Erro ao registrar a aposta. Tente novamente.');
            },
            complete: function() {
                $submitButton.prop('disabled', false).text('Salvar Alterações');
            }
        });
    });
    
    // --- DEPÓSITO ---
    $(document).on('submit', '#form-deposito-edit', function(event) {
        event.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');

        $submitButton.prop('disabled', true).text('Salvando...');

        const valorDeposito = parseFloat($form.find('input[name="valor"]').val()) || 0;
        
        const formData = {
            playerId: 123, 
            carteiraId: 5, 
            valor: valorDeposito,
            type: 0, //  TransasaoType::Deposito
            
            // Valores de Exemplo para a classe PaymanetHistorico
            
            gameId: 0, // Não se aplica
            gameNome: 'deposito',//descrição
            tempo: 0,
            
            // Campos adicionais do formulário
            metodo: $form.find('select[name="metodoPagamento"]').val(),
            recorrente: $form.find('input[name="recorrente"]').is(':checked')
        };

        $.ajax({
            url: API_ENDPOINT_DEPOSITO,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                alert('Configurações de depósito salvas com sucesso! Fechando modal...');
                $('#modalDeposito').modal('hide'); 
            },
            error: function(xhr) {
                console.error('Erro no AJAX (Depósito):', xhr.responseText);
                alert('Erro ao salvar as configurações de depósito. Tente novamente.');
            },
            complete: function() {
                $submitButton.prop('disabled', false).text('Salvar Configurações');
            }
        });
    });

    // --- RETIRADA/SAQUE ---
    $(document).on('submit', '#form-retirada-edit', function(event) {
        event.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');

        $submitButton.prop('disabled', true).text('Processando...');

        const valorSaque = parseFloat($form.find('input[name="valor"]').val()) || 0;
        
        const formData = {
            playerId: 123, 
            carteiraId: 5, 
            valor: valorSaque,
            type: 1, //  TransasaoType::Saque
            
            // Valores de Exemplo para a classe PaymanetHistorico
            gameId: 0,
            gameNome: 'Saque',//descrição 
            tempo: 0,
            
            // Campos adicionais do formulário
            metodo: $form.find('select[name="metodoSaque"]').val(),
            chave: $form.find('input[name="chaveSaque"]').val()
        };

        $.ajax({
            url: API_ENDPOINT_RETIRADA,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                alert('Dados de retirada salvos e processamento iniciado! Fechando modal...');
                $('#modalRetirada').modal('hide'); 
            },
            error: function(xhr) {
                console.error('Erro no AJAX (Retirada):', xhr.responseText);
                alert('Erro ao salvar dados de retirada. Tente novamente.');
            },
            complete: function() {
                $submitButton.prop('disabled', false).text('Salvar Dados de Saque');
            }
        });
    });

    // O MODAL DESAFIO (modal_desafio.html) não foi incluído aqui,
    // pois ele não tinha uma lógica de POST definida, mas pode ser adicionado
    // com um padrão similar, se necessário.

});