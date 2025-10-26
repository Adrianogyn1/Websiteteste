Ótima ideia\! Usar mais classes do Bootstrap transforma o layout.

Vou reestruturar o código HTML, colocando o chat dentro de um `card` (cartão) para um visual mais limpo e usando o sistema de grid do Bootstrap para responsividade e espaçamento.

O código PHP e o JavaScript já corrigidos foram incorporados.

### Código HTML/PHP Aprimorado com Bootstrap

```php
<?php

// GARANTINDO O USO DE $_POST CORRETO
if(isset($_POST["ligar"])){
    $script = __DIR__ . '/server.js';
    // Comando para rodar em segundo plano e retornar o PID
    $command = "nohup node {$script} > /dev/null 2>&1 & echo $!";
    $pid = shell_exec($command);
    echo "Servidor ligado! PID: " . trim($pid);
}

if(isset($_POST["desligar"])){
    // Tenta matar o processo Node que contém 'server.js'
    $output = shell_exec("pkill -f 'node server.js' 2>&1");
    
    if (empty($output)) {
        echo "Servidor desligado com sucesso!";
    } else {
        echo "Erro ao desligar o servidor ou servidor não encontrado: " . $output;
    }
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/eruda"></script>
    <script src="https://cdn.jsdelivr.net/npm/socket.io-client@4.7.5/dist/socket.io.min.js"></script>

    <style>
        /* Estilo para a caixa de mensagens: altura fixa e scroll */
        #messages { 
            height: 350px; /* Altura um pouco maior */
            overflow-y: auto; 
            padding: 10px;
            background-color: #f8f9fa; /* cor de fundo leve */
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
                            <button id="ligar" class="btn btn-success flex-fill">
                                <i class="bi bi-power me-1"></i> Ligar
                            </button>
                            <button id="desligar" class="btn btn-danger flex-fill">
                                <i class="bi bi-stop-circle-fill me-1"></i> Desligar
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
        // Termius já tem a chave privada, mas a conexão WebSocket é direta.
        const ws = new WebSocket('ws://35.209.27.45:3000');
        
        ws.onmessage = e => 
        {
            $('#messages').append('<div>' + e.data + '</div>');
            // Mantém o scroll no final
            $('#messages').scrollTop($('#messages')[0].scrollHeight);
        };

        $('#send').click(() => {
            const msg = $('#input').val();
            if(msg) ws.send(msg);
            $('#input').val('');
        });

        // Habilitar envio ao apertar ENTER no campo de input
        $('#input').keypress(function(e) {
            if (e.which == 13) {
                $('#send').click();
                return false; // Previne o submit de formulário
            }
        });
        
        // Função para mostrar o status (com classes de alerta do Bootstrap)
        function addStatus(message, type) {
            const $statusDiv = $('#status-message');
            $statusDiv.removeClass('d-none alert-success alert-danger');
            $statusDiv.addClass(type === 'success' ? 'alert-success' : 'alert-danger');
            $statusDiv.html(message);
        }

        // --- Eventos dos Botões (corrigidos) ---
        
        $("#ligar").click(function(){
            addStatus('Tentando ligar o servidor...', 'info'); // 'info' temporário
            $.post('', {ligar: true}, function(data){
                addStatus(data, 'success');
            }).fail(function() {
                addStatus("Erro na requisição para ligar.", 'danger');
            });
        });
        
        // Correção de ID de #desliga para #desligar
        $("#desligar").click(function(){
            addStatus('Tentando desligar o servidor...', 'info'); // 'info' temporário
            $.post('', { desligar: true }, function(data) {
                addStatus(data, 'success');
            }).fail(function() {
                addStatus("Erro na requisição para desligar.", 'danger');
            });
        });
        
        eruda.init();
    </script>
</body>
</html>
```