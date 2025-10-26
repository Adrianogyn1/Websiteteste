const WebSocket = require('ws');
const http = require('http');

// Definir a porta fora da string para evitar erro
const port = 3000; 
const server = http.createServer();
const wss = new WebSocket.Server({ server });

wss.on('connection', ws => 
{
    console.log('Cliente conectado!');
    
    // 💡 OTIMIZAÇÃO: Avisar apenas os outros clientes (que já estão conectados)
    // ws é o cliente que acabou de entrar, não precisa avisar a si mesmo.
    wss.clients.forEach(client => {
        // Verifica se o cliente está aberto E não é o cliente atual
        if (client.readyState === WebSocket.OPEN && client !== ws) {
            client.send('Um novo usuário entrou no chat!');
        }
    });
    
    ws.on('message', message => {
        // 💡 OTIMIZAÇÃO: Converte a mensagem para string antes de logar e enviar
        const messageString = message.toString();
        console.log('Mensagem recebida:', messageString);
            
        // Envia a mensagem para TODOS os clientes conectados
        wss.clients.forEach(client => {
            if (client.readyState === WebSocket.OPEN) {
                client.send(messageString);
            }
        });
    });

    ws.on('close', () => {
        console.log('Cliente desconectado.');
         // Avisa os clientes remanescentes sobre a desconexão
        wss.clients.forEach(client => {
            if (client.readyState === WebSocket.OPEN) {
                client.send('Um usuário saiu do chat.');
            }
        });
    });
});

// ❌ CORREÇÃO: Erro de sintaxe na linha de console.log. Use template literals ou concatenação.
server.listen(port, '0.0.0.0', () => {
    console.log(`Servidor de chat rodando na porta ${port}`); 
});