<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Chat Simples</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        #messages { border: 1px solid #ccc; height: 200px; overflow-y: scroll; padding: 5px; }
        #input { width: 80%; }
    </style>
</head>
<body>
    <h2>Chat Teste</h2>
    <div id="messages"></div>
    <input id="input" placeholder="Digite sua mensagem" />
    <button id="send">Enviar</button>

    <script>
        const ws = new WebSocket('ws://35.209.27.45:8888');
        ws.onmessage = e => {
            $('#messages').append('<div>' + e.data + '</div>');
            $('#messages').scrollTop($('#messages')[0].scrollHeight);
        };

        $('#send').click(() => {
            const msg = $('#input').val();
            if(msg) ws.send(msg);
            $('#input').val('');
        });
    </script>
</body>
</html>
