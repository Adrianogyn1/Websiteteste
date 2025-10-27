// server.js

// --- 1. CONFIGURAÇÕES BÁSICAS ---
const express = require('express');
const app = express();
const http = require('http').createServer(app);
const io = require('socket.io')(http, {
    // Configuração CORS: essencial para aceitar conexões de IPs diferentes
    // Ou de localhost, ou da origem nula (se for o caso de file:// - embora não recomendado)
    cors: {
        origin: ["*", null], // Permite qualquer origem e a origem 'null'
        methods: ["GET", "POST"]
    }
});

const PORT = 3000;

// Serve arquivos estáticos da pasta 'public'
app.use(express.static('public'));

// Rota básica para o arquivo HTML
app.get('/', (req, res) => {
    res.sendFile(__dirname + '/public/index.html');
});


// --- 2. LÓGICA DE SALAS E UTILITÁRIOS ---
let rooms = { 'Geral': { count: 0, sockets: {} } }; // Salas ativas e contagem de usuários
let userMap = {}; // Mapeia o nome do usuário para o ID do Socket (para DMs)

// Função auxiliar para gerar um nome de sala consistente para DM
function getPrivateRoomName(user1, user2) {
    // Garante que o nome da sala seja o mesmo, independentemente da ordem
    const sortedUsers = [user1, user2].sort();
    return `DM_${sortedUsers[0]}_${sortedUsers[1]}`;
}

// Envia a lista de salas atualizada para todos os clientes
function updateRoomList() {
    const activeRooms = Object.keys(rooms).map(roomName => ({
        name: roomName,
        count: rooms[roomName].count
    }));
    io.emit('room list update', activeRooms);
}


// --- 3. EVENTOS DO SOCKET.IO ---
io.on('connection', (socket) => {
    let currentUsername = null;
    let currentRoom = null;
    
    console.log(`[CONEXÃO] Novo usuário conectado: ${socket.id}`);
    
    
    // --- ENTRAR NA SALA ---
    socket.on('join room', (roomName, username, callback) => {
        // 1. Validar e Sair da Sala Antiga
        if (currentRoom) {
            // Remove o socket da sala antiga
            socket.leave(currentRoom);
            
            // Decrementa o contador da sala antiga
            if (rooms[currentRoom]) {
                rooms[currentRoom].count--;
                delete rooms[currentRoom].sockets[socket.id];
                
                // Remove a sala se estiver vazia (exceto a sala 'Geral')
                if (rooms[currentRoom].count <= 0 && currentRoom !== 'Geral') {
                    delete rooms[currentRoom];
                }
            }
            
            // Notifica os outros sobre a saída
            io.to(currentRoom).emit('chat message', {
                user: 'Sistema',
                msg: `${currentUsername} saiu da sala.`,
                room: currentRoom,
                type: 'system'
            });
        }
        
        // 2. Entrar na Nova Sala
        currentUsername = username;
        currentRoom = roomName;
        userMap[currentUsername] = socket.id; // Atualiza o mapeamento de usuário
        
        socket.join(currentRoom);
        
        // Cria a sala se não existir (apenas para contagem)
        if (!rooms[currentRoom]) {
            rooms[currentRoom] = { count: 0, sockets: {} };
        }
        rooms[currentRoom].count++;
        rooms[currentRoom].sockets[socket.id] = true;
        
        // 3. Notificações e Callbacks
        console.log(`[JOIN] ${currentUsername} (${socket.id}) entrou em: ${currentRoom}`);
        
        // Feedback para o próprio usuário (Mensagem de Sistema)
        socket.emit('chat message', {
            user: 'Sistema',
            msg: `Você entrou na sala/chat: ${currentRoom}`,
            room: currentRoom,
            type: 'system'
        });
        
        // Broadcast para a sala sobre o novo membro
        socket.to(currentRoom).emit('chat message', {
            user: 'Sistema',
            msg: `${currentUsername} entrou na sala.`,
            room: currentRoom,
            type: 'system'
        });
        
        updateRoomList();
        if (callback) {
            callback(currentRoom);
        }
    });
    
    
    // --- CHAT MESSAGE ---
    socket.on('chat message', (msg) => {
        if (currentRoom && currentUsername && msg.trim()) {
            const data = {
                user: currentUsername,
                msg: msg,
                room: currentRoom,
                type: 'user'
            };
            // Envia a mensagem para todos, incluindo o remetente, na sala
            io.to(currentRoom).emit('chat message', data);
        }
    });
    
    
    // --- LISTA DE USUÁRIOS (para DM) ---
    socket.on('get active users', (callback) => {
        const activeUsers = Object.keys(userMap).filter(user => user !== currentUsername);
        if (callback) callback(activeUsers);
    });
    
    
    // --- DESCONEXÃO ---
    socket.on('disconnect', () => {
        console.log(`[DESCONEXÃO] Usuário desconectado: ${socket.id}`);
        
        if (currentUsername) {
            // Limpa o mapeamento de usuário
            delete userMap[currentUsername];
        }
        
        if (currentRoom && rooms[currentRoom]) {
            // Decrementa o contador da sala
            rooms[currentRoom].count--;
            delete rooms[currentRoom].sockets[socket.id];
            
            // Se a sala ficar vazia (e não for 'Geral'), remove
            if (rooms[currentRoom].count <= 0 && currentRoom !== 'Geral') {
                delete rooms[currentRoom];
            } else {
                // Notifica a sala sobre a saída
                io.to(currentRoom).emit('chat message', {
                    user: 'Sistema',
                    msg: `${currentUsername} desconectou.`,
                    room: currentRoom,
                    type: 'system'
                });
            }
        }
        updateRoomList();
    });
});


// --- 4. INICIALIZAÇÃO DO SERVIDOR HTTP ---
http.listen(PORT, () => {
    console.log(`Servidor Node.js rodando em http://localhost:${PORT}`);
    console.log(`Inicie o servidor em background com: pm2 start server.js --name "MeuChatServer"`);
});