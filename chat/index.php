<?php

// =======================================================
// Lógica de Controle do Servidor (PHP)
// =======================================================

// LIGAR
if(isset($_POST["ligar"])){
    $script = __DIR__ . '/server.js';
    // Comando para rodar em segundo plano e retornar o PID
    $command = "nohup node {$script} > /dev/null 2>&1 & echo $!";
    $pid = shell_exec($command);
    echo "Servidor ligado! PID: " . trim($pid);
    exit;
}

// DESLIGAR
// No seu arquivo PHP, bloco 'desligar'
if(isset($_POST["desligar"])){
    // Agora o comando usa sudo e o sinal -9 para forçar o encerramento
    $output = shell_exec("sudo pkill -9 -f 'node server.js' 2>&1");
    
    if (empty($output)) {
        echo "Servidor desligado com sucesso!";
    } else {
        echo "Erro ao desligar o servidor ou servidor não encontrado: " . $output;
    }
    exit;
}

// VERIFICAR STATUS
if(isset($_POST["status"])){
    // Tenta encontrar o PID do processo 'node server.js'
    $pid = shell_exec("pgrep -f 'node server.js' 2>&1");
    
    if (!empty($pid)) {
        // Retorna o status de forma simples para o JavaScript
        echo "rodando | PID: " . trim($pid);
    } else {
        echo "parado";
    }
    // O PHP deve parar aqui para não retornar o HTML junto com o status
    exit; 
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chat Simples & Controle do Servidor</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/eruda"></script>
    <script src="https://cdn.jsdelivr.net/npm/socket.io-client@4.7.5/dist/socket.io.min.js"></script>
    
    <style>
        #messages { 
            height: 350px;
            overflow-y: auto; 
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 0.25rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Chat Teste & Controle</h4>
                        <i class="bi bi-chat-dots-fill fs-4"></i>
                    </div>
                    
                    <div class="card-body">
                        
                        <div class="mb-3 d-flex justify-content-between gap-2">
                            
                            <button id="ligar" class="btn btn-success flex-fill d-none">
                                <i class="bi bi-power me-1"></i> Ligar
                            </button>
                            <button id="desligar" class="btn btn-danger flex-fill d-none">
                                <i class="bi bi-stop-circle-fill me-1"></i> Desligar
                            </button>
                            
                            <button id="status-check" class="btn btn-info flex-fill">
                                <i class="bi bi-question-circle-fill me-1"></i> Status Atual
                            </button>
                        </div>
                        
                        <div id="status-message" class="alert d-none" role="alert"></div>

                        <div id="messages" class="mb-3 border">
                            </div>

                        <div class="input-group">
                            <input id="input" type="text" class="form-control" placeholder="Digite sua mensagem..." aria-label="Mensagem">
                            <button id="send" class="btn btn-primary" type="button">
                                <i class="bi bi-send-fill"></i> Enviar
                            </button>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <script>
        // Use a porta correta: 3000
        const ws = new WebSocket('ws://35.209.27.45:3000'); 
        
        // --- FUNÇÕES DE CHAT ---
        ws.onmessage = e => 
        {
            $('#messages').append('<div>' + e.data + '</div>');
            $('#messages').scrollTop($('#messages')[0].scrollHeight);
        };

        $('#send').click(() => {
            const msg = $('#input').val();
            if(msg) ws.send(msg);
            $('#input').val('');
        });

        $('#input').keypress(function(e) {
            if (e.which == 13) {
                $('#send').click();
                return false;
            }
        });
        
        // --- FUNÇÕES DE STATUS E UI ---
        
        function addStatus(message, type) {
            const $statusDiv = $('#status-message');
            $statusDiv.removeClass('d-none alert-success alert-danger alert-info');
            $statusDiv.addClass(type === 'success' ? 'alert-success' : type === 'danger' ? 'alert-danger' : 'alert-info');
            $statusDiv.html(message);
        }

        function updateButtonVisibility(status, pidInfo) {
            const $ligar = $('#ligar');
            const $desligar = $('#desligar');
            
            // Esconde os botões de ligar/desligar
            $ligar.addClass('d-none');
            $desligar.addClass('d-none');

            // Define qual botão deve aparecer e atualiza o status
            if (status === 'rodando') {
                $desligar.removeClass('d-none');
                addStatus(`Servidor RODANDO! ${pidInfo}`, 'success');
            } else { // 'parado'
                $ligar.removeClass('d-none');
                addStatus("Servidor PARADO.", 'danger');
            }
        }
        
        function checkServerStatus() {
            $.post('', { status: true }, function(data) {
                const [status, pidInfo] = data.split(' | '); 
                updateButtonVisibility(status, pidInfo);
            }).fail(function() {
                addStatus("Erro ao comunicar com o servidor web (PHP).", 'danger');
            });
        }

        // --- EVENTOS DOS BOTÕES ---
        
        $("#ligar").click(function(){
            addStatus('Iniciando o servidor...', 'info');
            $.post('', {ligar: true}, function(data){
                // Após ligar, verifica o status para atualizar os botões
                checkServerStatus(); 
            }).fail(function() {
                addStatus("Erro na requisição para ligar.", 'danger');
            });
        });
        
        $("#desligar").click(function(){
            addStatus('Encerrando o servidor...', 'info');
            $.post('', { desligar: true }, function(data) {
                // Após desligar, verifica o status para atualizar os botões
                checkServerStatus();
            }).fail(function() {
                addStatus("Erro na requisição para desligar.", 'danger');
            });
        });
        
        // Botão de verificação manual (opcional, mas útil)
        $("#status-check").click(checkServerStatus);

        // --- INICIALIZAÇÃO E LOOP DE VERIFICAÇÃO ---

        $(document).ready(function() {
            // 1. Executa a primeira verificação imediatamente ao carregar
            checkServerStatus(); 
            
            // 2. Define o loop de verificação automática a cada 5 segundos (5000ms)
            setInterval(checkServerStatus, 5000); 
            
            eruda.init();
        });
    </script>
</body>
</html>