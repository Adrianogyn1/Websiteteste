<?php
if(isset($_post["ligar"])){
    $script = __DIR__ . '/server.js';
    $output = shell_exec('node '.$script);
echo "<pre>$output</pre>";
}

if(isset($_post["desligar"])){

// Exemplo para Linux
$output = shell_exec("pkill -f 'server.js' 2>&1");
echo $output ?: "Servidor desligado! ";

}



?>




<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Chat Simples</title>
    <!-- Ícones -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Date Range Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <!-- Eruda -->
    <script src="//cdn.jsdelivr.net/npm/eruda"></script>

<script src="https://cdn.jsdelivr.net/npm/socket.io-client@4.7.5/dist/socket.io.min.js"></script>

    <style>
        #messages { border: 1px solid #ccc; height: 200px; overflow-y: scroll; padding: 5px; }
        #input { width: 80%; }
    </style>
</head>
<body>
    <div>
            <button id="ligar" class="btn-info">ligar</button>
             <button id="desligar" class="btn-info">desligar</button>
            
    </div>
    <h2>Chat Teste</h2>
    <div id="messages"></div>
    <input id="input" placeholder="Digite sua mensagem" />
    <button id="send" class="btn-info">Enviar</button>

    <script>
        const ws = new WebSocket('ws://35.209.27.45:3000');
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
        
        $("ligar").click(function(){
            $.post('/')?????
        })
        
            $("desliga").click(function(){
            $.post('/')?????
        })
    </script>
    
    <script>eruda.init();</script>
</body>
</html>
