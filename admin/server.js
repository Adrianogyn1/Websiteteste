// server.js (Versão com Persistência de Histórico e Privacidade de DM CORRIGIDA)

const express = require('express');
const app = express();
const http = require('http').createServer(app);
const io = require('socket.io')(http, {
    cors: {
        origin: ["*", null], 
        methods: ["GET", "POST"]
    }
});
const fs = require('fs'); 

const PORT = 3000;
const HISTORY_FILE = 'chat_history.json';
const HISTORY_LIMIT = 100; 

// --- DADOS PERSISTENTES NA MEMÓRIA ---
let rooms = { 'Geral': { count: 0, sockets: {} } }; 
let userMap = {}; // userMap[username] = socket.id
let roomHistory = {}; 


// --- FUNÇÕES DE PERSISTÊNCIA EM ARQUIVO (MANTIDAS) ---

function loadHistoryFromDisk() {
    try {
        if (fs.existsSync(HISTORY_FILE)) {
            const data = fs.readFileSync(HISTORY_FILE, 'utf8');
            console.log("[PERSISTÊNCIA] Histórico carregado do disco.");
            const loadedHistory = JSON.parse(data);
            if (!loadedHistory['Geral']) {
                loadedHistory['Geral'] = [];
            }
            return loadedHistory;
        } else {
            console.log("[PERSISTÊNCIA] Arquivo de histórico não encontrado. Criando novo.");
            return { 'Geral': [] };
        }
    } catch (error) {
        console.error("[ERRO PERSISTÊNCIA] Falha ao carregar histórico:", error.message);
        return { 'Geral': [] }; 
    }
}

function saveHistoryToDisk() {
    try {
        const data = JSON.stringify(roomHistory, null, 2);
        fs.writeFile(HISTORY_FILE, data, 'utf8', (err) => {
            if (err) {
                console.error("[ERRO PERSISTÊNCIA] Falha ao salvar histórico:", err.message);
            }
        });
    } catch (error) {
        console.error("[ERRO PERSISTÊNCIA] Falha ao serializar histórico:", error.message);
    }
}


// --- CONFIGURAÇÃO DO EXPRESS (MANTIDA) ---
app.use(express.static('public'));

app.get('/', (req, res) => {
    res.sendFile(__dirname + '/public/index.html');
});


// --- LÓGICA DE SALAS E UTILITÁRIOS: CORREÇÃO DE PRIVACIDADE ---

function updateRoomList() {
    // 1. Coleta todas as salas (Geral, Públicas e DMs)
    const allRooms = new Set([...Object.keys(rooms), ...Object.keys(roomHistory)]);
    
    // 2. Itera sobre todos os usuários que têm um mapeamento ativo no servidor
    Object.keys(userMap).forEach(currentUsername => {
        const socketId = userMap[currentUsername];
        const socket = io.sockets.sockets.get(socketId);
        
        if (!socket) return; // Se o socket não estiver mais ativo, pula.

        // 3. Constrói a lista de salas visíveis para este usuário (currentUsername)
        const visibleRooms = Array.from(allRooms)
            .filter(roomName => {
                const hasHistory = roomHistory[roomName] && roomHistory[roomName].length > 0;
                const hasUsers = rooms[roomName] && rooms[roomName].count > 0;
                const isDM = roomName.startsWith('DM_');

                // Salas Públicas (incluindo Geral) são visíveis se tiverem histórico ou usuários
                if (roomName === 'Geral' || (!isDM && (hasHistory || hasUsers))) {
                    return true; 
                }

                // Salas de DM: Só são visíveis se o nome do usuário estiver na chave.
                if (isDM) {
                    // Verifica se o nome do usuário está presente na chave da DM (Ex: DM_alice_bob)
                    const isUserInDM = roomName.includes(`_${currentUsername}_`) || roomName.endsWith(`_${currentUsername}`) || roomName.startsWith(`DM_${currentUsername}_`);
                    
                    return isUserInDM && (hasHistory || hasUsers);
                }
                
                return false;
            })
            .map(roomName => ({
                name: roomName,
                count: rooms[roomName] ? rooms[roomName].count : 0
            }));

        // 4. Envia a lista FILTRADA apenas para este socket.
        io.to(socketId).emit('room list update', visibleRooms);
    });
}


// --- EVENTOS DO SOCKET.IO ---
io.on('connection', (socket) => {
    let currentUsername = null;
    let currentRoom = null;

    console.log(`[CONEXÃO] Novo usuário conectado: ${socket.id}`);

    // --- ENTRAR NA SALA ---
    socket.on('join room', (roomName, username, callback) => {
        
        // 1. Sair da Sala Antiga e Atualizar Contagem
        if (currentRoom) {
            socket.leave(currentRoom); 
            
            if (rooms[currentRoom] && rooms[currentRoom].sockets[socket.id]) {
                rooms[currentRoom].count--;
                delete rooms[currentRoom].sockets[socket.id];
                // Notificação de saída (membros restantes)
                if (rooms[currentRoom].count > 0 && currentRoom !== 'Geral' && !currentRoom.startsWith('DM_')) {
                    io.to(currentRoom).emit('chat message', {
                        user: 'Sistema', msg: `${currentUsername} saiu da sala.`, room: currentRoom, type: 'system'
                    });
                }
            }
        }

        // 2. Atualização do Estado do Usuário
        if (currentUsername && userMap[currentUsername] === socket.id && currentUsername !== username) {
             // Limpa o mapeamento antigo se o nome está mudando
             delete userMap[currentUsername]; 
        }

        currentUsername = username;
        currentRoom = roomName;
        userMap[currentUsername] = socket.id; // Mapeamento crucial para updateRoomList personalizada
        
        // 3. Entrar na Nova Sala e Atualizar Estado da Sala
        socket.join(currentRoom);

        if (!rooms[currentRoom]) {
            rooms[currentRoom] = { count: 0, sockets: {} };
        }
        
        rooms[currentRoom].count++;
        rooms[currentRoom].sockets[socket.id] = true;
        
        if (!roomHistory[currentRoom]) {
             roomHistory[currentRoom] = [];
        }
        
        // 4. Enviar Histórico e Notificações
        socket.emit('load history', roomHistory[currentRoom]);

        socket.emit('chat message', { 
            user: 'Sistema', msg: `Você entrou na sala/chat: ${currentRoom}`, room: currentRoom, type: 'system' 
        });

        if (currentRoom !== 'Geral' && !currentRoom.startsWith('DM_')) {
            socket.to(currentRoom).emit('chat message', {
                user: 'Sistema', msg: `${currentUsername} entrou na sala.`, room: currentRoom, type: 'system'
            });
        }
        
        console.log(`[JOIN] ${currentUsername} (${socket.id}) entrou em: ${currentRoom}`);
        updateRoomList(); // Envia a lista personalizada
        if (callback) {
            callback(currentRoom);
        }
    });


    // --- CHAT MESSAGE ---
    socket.on('chat message', (msg) => {
        if (currentRoom && currentUsername && msg.trim()) {
            const data = {
                user: currentUsername,
                msg: msg.trim(),
                room: currentRoom,
                type: 'user',
                timestamp: Date.now() 
            };
            
            if (!roomHistory[currentRoom]) {
                roomHistory[currentRoom] = [];
            }
            
            roomHistory[currentRoom].push(data);
            if (roomHistory[currentRoom].length > HISTORY_LIMIT) {
                roomHistory[currentRoom].shift(); 
            }

            saveHistoryToDisk(); 

            io.to(currentRoom).emit('chat message', data);
        }
    });
    
    
    // --- LISTA DE USUÁRIOS (MANTIDO) ---
    socket.on('get active users', (callback) => {
        const activeUsers = Object.keys(userMap);
        if (callback) callback(activeUsers);
    });


    // --- DESCONEXÃO ---
    socket.on('disconnect', () => {
        console.log(`[DESCONEXÃO] Usuário desconectado: ${currentUsername} (${socket.id})`);
        
        if (currentUsername && userMap[currentUsername] === socket.id) {
            delete userMap[currentUsername];
            console.log(`[LIMPEZA] Mapeamento de usuário removido: ${currentUsername}`);
        }

        if (currentRoom && rooms[currentRoom] && rooms[currentRoom].sockets[socket.id]) {
            rooms[currentRoom].count--;
            delete rooms[currentRoom].sockets[socket.id];
            
            if (rooms[currentRoom].count > 0 && !currentRoom.startsWith('DM_') && currentRoom !== 'Geral') {
                io.to(currentRoom).emit('chat message', {
                    user: 'Sistema', msg: `${currentUsername} desconectou.`, room: currentRoom, type: 'system'
                });
            }
        }
        updateRoomList(); // Envia a lista personalizada para todos os restantes
    });
});


// --- INICIALIZAÇÃO DO SERVIDOR HTTP ---

roomHistory = loadHistoryFromDisk();

http.listen(PORT, () => {
    console.log(`Servidor Node.js rodando em http://localhost:${PORT}`);
    console.log(`Histórico persistente será salvo em: ${HISTORY_FILE}`);
});