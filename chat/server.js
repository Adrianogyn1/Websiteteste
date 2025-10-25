const WebSocket = require('ws');
const http = require('http');

const server = http.createServer();
const wss = new WebSocket.Server({ server });

wss.on('connection', ws => {
    ws.on('message', message => {
        // Envia a mensagem para todos os clientes conectados
        wss.clients.forEach(client => {
            if (client.readyState === WebSocket.OPEN) {
                client.send(message);
            }
        });
    });
});

server.listen(8888, () => {
    console.log('Servidor de chat rodando na porta 3000');
});
