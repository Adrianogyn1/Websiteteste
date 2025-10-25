const WebSocket = require('ws');
const http = require('http');
const port =3000;
const server = http.createServer();
const wss = new WebSocket.Server({ server });

wss.on('connection', ws => 
{
    console.log('Cliente conectado!');
    
         wss.clients.forEach(client => {
                 if (client.readyState === WebSocket.OPEN) {
                     client.send('entrou');
                 }});
    
    ws.on('message', message => {
        console.log('Mensagem recebida:', message);
   
            
        wss.clients.forEach(client => {
            if (client.readyState === WebSocket.OPEN) {
                client.send(message);
            }
        });
    });
});

server.listen(port, '0.0.0.0', () => {
    console.log('Servidor de chat rodando na porta $`port`');
});
