const express = require('express');
const app = express();
const http = require('http');
const server = http.createServer(app);
const { Server } = require('socket.io');
const io = new Server(server, {
    cors: {
        origin: "*", // Permite conexões de qualquer origem para facilitar testes
    }
});
const path = require('path');

// --- Função para Gerenciar e Emitir a Lista de Salas ---
function getActiveRooms() {
    // Pega todas as salas (que não são IDs de sockets individuais)
    const rooms = io.sockets.adapter.rooms;
    const activeRooms = [];
    
    // O Socket.IO armazena o ID do socket como uma "sala", precisamos filtrar isso.
    rooms.forEach((setOfSockets, roomName) => {
        // Se a sala não for o ID de um socket (e tiver pelo menos um cliente)
        if (!setOfSockets.has(roomName)) {
            activeRooms.push({
                name: roomName,
                count: setOfSockets.size
            });
        }
    });
    // Sempre teremos pelo menos uma sala 'Geral' para começar
    if (!activeRooms.find(r => r.name === 'Geral')) {
        activeRooms.unshift({ name: 'Geral', count: 0 });
    }
    
    return activeRooms;
}

function broadcastRoomList() {
    const rooms = getActiveRooms();
    io.emit('room list update', rooms); // Envia para TODOS os clientes
}
// --------------------------------------------------------

// Configuração do Express
app.use(express.static(path.join(__dirname, 'public')));
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// Lógica do Socket.IO
io.on('connection', (socket) => {
    console.log('Usuário conectado:', socket.id);
    
    // Envia a lista de salas assim que o usuário se conecta
    broadcastRoomList();
    
    // Evento para entrar em uma sala
    socket.on('join room', (roomName, username, callback) => {
        const previousRoom = socket.data.room;
        
        // 1. Sair da sala anterior
        if (previousRoom) {
            socket.leave(previousRoom);
            // Notificar a sala anterior sobre a saída
            io.to(previousRoom).emit('chat message', {
                user: 'Sistema',
                msg: `${username} saiu da sala.`,
                type: 'system'
            });
        }
        
        // 2. Entrar na nova sala
        socket.join(roomName);
        socket.data.username = username;
        socket.data.room = roomName;
        
        // 3. Notificar a nova sala sobre a entrada
        io.to(roomName).emit('chat message', {
            user: 'Sistema',
            msg: `${username} entrou na sala.`,
            type: 'system'
        });
        
        console.log(`Usuário ${username} mudou para a sala: ${roomName}`);
        
        // 4. Atualizar a lista de salas para todos (novo join/leave pode ter criado/esvaziado uma sala)
        broadcastRoomList();
        
        // 5. Callback de sucesso
        if (callback) {
            callback(roomName);
        }
    });
    
    // Evento para receber e retransmitir mensagens
    socket.on('chat message', (msg) => {
        const room = socket.data.room;
        const user = socket.data.username || 'Anônimo';
        
        if (room && user) {
            const messageData = {
                user: user,
                msg: msg,
                room: room,
                type: 'user'
            };
            // Envia para todos na sala
            io.to(room).emit('chat message', messageData);
            console.log(`[${room}] ${user}: ${msg}`);
        }
    });
    
    // Evento de desconexão
    socket.on('disconnect', () => {
        const user = socket.data.username;
        const room = socket.data.room;
        
        if (room && user) {
            // Notificar a sala sobre a desconexão
            io.to(room).emit('chat message', {
                user: 'Sistema',
                msg: `${user} saiu da sala.`,
                type: 'system'
            });
        }
        console.log(`Usuário desconectado: ${socket.id}`);
        
        // Atualizar a lista de salas (um usuário a menos)
        // Pequeno delay para garantir que o socket.leave seja processado internamente
        setTimeout(broadcastRoomList, 50);
    });
});

const PORT = 3000;
server.listen(PORT, '0.0.0.0', () => { // Escutar em 0.0.0.0 para ser acessível externamente
    console.log(`Servidor rodando em http://35.209.27.45:${PORT} (ou localhost)`);
});