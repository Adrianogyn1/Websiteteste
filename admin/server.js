// server.js (REVISADO)

const express = require('express');
const app = express();
const http = require('http').createServer(app);
const io = require('socket.io')(http, {
    cors: {
        origin: ["*", null],
        methods: ["GET", "POST"]
    }
});

const PORT = 3000;

// --- DADOS PERSISTENTES NO SERVIDOR ---
let rooms = { 'Geral': { count: 0, sockets: {} } }; // Salas ativas e contagem de usuários
let userMap = {}; // Mapeia o nome do usuário para o ID do Socket
let roomHistory = { 
    'Geral': [] // Inicializa o histórico da sala Geral
}; 
const HISTORY_LIMIT = 100; // Limite de mensagens por sala

// Serve arquivos estáticos da pasta 'public'
app.use(express.static('public'));

app.get('/', (req, res) => {
    res.sendFile(__dirname + '/public/index.html');
});


// --- LÓGICA DE SALAS E UTILITÁRIOS ---

function getPrivateRoomName(user1, user2) {
    const sortedUsers = [user1, user2].sort();
    return `DM_${sortedUsers[0]}_${sortedUsers[1]}`;
}

function updateRoomList() {
    // A lista de salas ativas deve incluir todas as salas com histórico,
    // mesmo que estejam vazias (para persistência do DM).
    const allRooms = new Set([...Object.keys(rooms), ...Object.keys(roomHistory)]);

    const activeRooms = Array.from(allRooms)
        .filter(roomName => {
            // Filtra DMs sem histórico e DMs onde o usuário é Anônimo (não precisa ser persistente).
            if (roomName.startsWith('DM_') && (!roomHistory[roomName] || roomHistory[roomName].length === 0)) {
                 // DMs vazias *só* são mantidas se tiverem histórico.
                 return false;
            }
            return true;
        })
        .map(roomName => ({
            name: roomName,
            count: rooms[roomName] ? rooms[roomName].count : 0
        }));
        
    io.emit('room list update', activeRooms);
}


// --- EVENTOS DO SOCKET.IO ---
io.on('connection', (socket) => {
    let currentUsername = null;
    let currentRoom = null;

    console.log(`[CONEXÃO] Novo usuário conectado: ${socket.id}`);


    // --- ENTRAR NA SALA (AGORA CARREGA O HISTÓRICO) ---
    socket.on('join room', (roomName, username, callback) => {
        // 1. Validar e Sair da Sala Antiga
        if (currentRoom) {
            socket.leave(currentRoom); 
            
            if (rooms[currentRoom]) {
                rooms[currentRoom].count--;
                delete rooms[currentRoom].sockets[socket.id];
                
                // NUNCA MAIS EXCLUIMOS A SALA AQUI. A PERSISTÊNCIA é baseada no histórico.
            }
            
            // Notifica os outros sobre a saída (apenas se o chat não for DM)
            if (!currentRoom.startsWith('DM_') && currentRoom !== 'Geral') {
                io.to(currentRoom).emit('chat message', {
                    user: 'Sistema', msg: `${currentUsername} saiu da sala.`, room: currentRoom, type: 'system'
                });
            }
        }
        
        // 2. Entrar na Nova Sala
        currentUsername = username;
        currentRoom = roomName;
        userMap[currentUsername] = socket.id; 
        
        socket.join(currentRoom);

        // Inicializa a sala e o histórico se não existirem
        if (!rooms[currentRoom]) {
            rooms[currentRoom] = { count: 0, sockets: {} };
        }
        rooms[currentRoom].count++;
        rooms[currentRoom].sockets[socket.id] = true;
        
        if (!roomHistory[currentRoom]) {
             roomHistory[currentRoom] = [];
        }
        
        // 3. Enviar Histórico (NOVO)
        socket.emit('load history', roomHistory[currentRoom]);

        // 4. Notificações e Callbacks
        console.log(`[JOIN] ${currentUsername} (${socket.id}) entrou em: ${currentRoom}`);

        socket.emit('chat message', { 
            user: 'Sistema', msg: `Você entrou na sala/chat: ${currentRoom}`, room: currentRoom, type: 'system' 
        });

        // Broadcast (apenas se o chat não for DM)
        if (!currentRoom.startsWith('DM_') && currentRoom !== 'Geral') {
            socket.to(currentRoom).emit('chat message', {
                user: 'Sistema', msg: `${currentUsername} entrou na sala.`, room: currentRoom, type: 'system'
            });
        }
        
        updateRoomList();
        if (callback) {
            callback(currentRoom);
        }
    });


    // --- CHAT MESSAGE (AGORA SALVA NO HISTÓRICO) ---
    socket.on('chat message', (msg) => {
        if (currentRoom && currentUsername && msg.trim()) {
            const data = {
                user: currentUsername,
                msg: msg.trim(),
                room: currentRoom,
                type: 'user',
                timestamp: Date.now() // Adiciona timestamp para ordenação
            };
            
            // 1. Salvar no histórico
            if (!roomHistory[currentRoom]) {
                roomHistory[currentRoom] = [];
            }
            roomHistory[currentRoom].push(data);

            // 2. Aplicar limite de histórico
            if (roomHistory[currentRoom].length > HISTORY_LIMIT) {
                // Remove a mensagem mais antiga (a primeira do array)
                roomHistory[currentRoom].shift(); 
            }

            // 3. Enviar a todos na sala
            io.to(currentRoom).emit('chat message', data);
        }
    });
    
    
    // --- LISTA DE USUÁRIOS (mantido) ---
    socket.on('get active users', (callback) => {
        const activeUsers = Object.keys(userMap).filter(user => user !== currentUsername);
        if (callback) callback(activeUsers);
    });


    // --- DESCONEXÃO ---
    socket.on('disconnect', () => {
        console.log(`[DESCONEXÃO] Usuário desconectado: ${socket.id}`);
        
        if (currentUsername) {
            delete userMap[currentUsername];
        }

        if (currentRoom && rooms[currentRoom]) {
            rooms[currentRoom].count--;
            delete rooms[currentRoom].sockets[socket.id];

            // Notifica se a sala não for DM
            if (rooms[currentRoom].count > 0 && !currentRoom.startsWith('DM_') && currentRoom !== 'Geral') {
                io.to(currentRoom).emit('chat message', {
                    user: 'Sistema', msg: `${currentUsername} desconectou.`, room: currentRoom, type: 'system'
                });
            }
        }
        updateRoomList();
    });
});


// --- INICIALIZAÇÃO DO SERVIDOR HTTP ---
http.listen(PORT, () => {
    console.log(`Servidor Node.js rodando em http://localhost:${PORT}`);
});