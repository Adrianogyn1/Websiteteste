// server.js (Versão com Persistência de Histórico em Arquivo JSON)

const express = require('express');
const app = express();
const http = require('http').createServer(app);
const io = require('socket.io')(http, {
    cors: {
        origin: ["*", null], 
        methods: ["GET", "POST"]
    }
});
const fs = require('fs'); // Importa o módulo File System

const PORT = 3000;
const HISTORY_FILE = 'chat_history.json';
const HISTORY_LIMIT = 100; // Limite de mensagens por sala/DM

// --- DADOS PERSISTENTES NA MEMÓRIA ---
let rooms = { 'Geral': { count: 0, sockets: {} } }; 
let userMap = {}; 
let roomHistory = {}; // Será carregado do arquivo

// --- FUNÇÕES DE PERSISTÊNCIA EM ARQUIVO ---

/**
 * Carrega o histórico do arquivo JSON ou retorna um objeto vazio se o arquivo não existir.
 */
function loadHistoryFromDisk() {
    try {
        if (fs.existsSync(HISTORY_FILE)) {
            const data = fs.readFileSync(HISTORY_FILE, 'utf8');
            console.log("[PERSISTÊNCIA] Histórico carregado do disco.");
            // Garante que 'Geral' exista, mesmo que o arquivo esteja vazio
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
        return { 'Geral': [] }; // Retorna estado padrão em caso de erro
    }
}

/**
 * Salva o histórico atual da memória no arquivo JSON de forma assíncrona.
 */
function saveHistoryToDisk() {
    try {
        const data = JSON.stringify(roomHistory, null, 2); // null, 2 para formatação legível
        // Usamos writeFile assíncrono para não travar o loop de eventos I/O do servidor
        fs.writeFile(HISTORY_FILE, data, 'utf8', (err) => {
            if (err) {
                console.error("[ERRO PERSISTÊNCIA] Falha ao salvar histórico:", err.message);
            }
            // Não logamos o sucesso a cada salvamento para evitar flood de console
        });
    } catch (error) {
        console.error("[ERRO PERSISTÊNCIA] Falha ao serializar histórico:", error.message);
    }
}


// --- CONFIGURAÇÃO DO EXPRESS ---
app.use(express.static('public'));

app.get('/', (req, res) => {
    res.sendFile(__dirname + '/public/index.html');
});


// --- LÓGICA DE SALAS E UTILITÁRIOS ---

function updateRoomList() {
    const allRooms = new Set([...Object.keys(rooms), ...Object.keys(roomHistory)]);

    const activeRooms = Array.from(allRooms)
        .filter(roomName => {
            const hasHistory = roomHistory[roomName] && roomHistory[roomName].length > 0;
            const hasUsers = rooms[roomName] && rooms[roomName].count > 0;
            const isDM = roomName.startsWith('DM_');

            if (roomName === 'Geral') return true; 

            if (isDM) {
                return hasHistory || hasUsers;
            }
            
            return hasHistory || hasUsers;
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

    // --- ENTRAR NA SALA ---
    socket.on('join room', (roomName, username, callback) => {
        
        // 1. Sair da Sala Antiga
        if (currentRoom) {
            socket.leave(currentRoom); 
            
            if (rooms[currentRoom]) {
                if (rooms[currentRoom].sockets[socket.id]) {
                    rooms[currentRoom].count--;
                    delete rooms[currentRoom].sockets[socket.id];
                }

                if (rooms[currentRoom].count > 0 && currentRoom !== 'Geral' && !currentRoom.startsWith('DM_')) {
                    io.to(currentRoom).emit('chat message', {
                        user: 'Sistema', msg: `${currentUsername} saiu da sala.`, room: currentRoom, type: 'system'
                    });
                }
            }
        }

        // 2. Atualização do Estado do Usuário
        if (currentUsername && currentUsername !== username) {
             delete userMap[currentUsername]; 
        }

        currentUsername = username;
        currentRoom = roomName;
        userMap[currentUsername] = socket.id; 
        
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
        updateRoomList();
        if (callback) {
            callback(currentRoom);
        }
    });


    // --- CHAT MESSAGE (AGORA CHAMA saveHistoryToDisk) ---
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
            
            // 1. Adiciona e limita
            roomHistory[currentRoom].push(data);
            if (roomHistory[currentRoom].length > HISTORY_LIMIT) {
                roomHistory[currentRoom].shift(); 
            }

            // 2. Salva no disco após cada mensagem (garantindo persistência imediata)
            saveHistoryToDisk(); 

            // 3. Envia para o cliente
            io.to(currentRoom).emit('chat message', data);
        }
    });
    
    
    // --- LISTA DE USUÁRIOS (MANTIDO) ---
    socket.on('get active users', (callback) => {
        const activeUsers = Object.keys(userMap);
        if (callback) callback(activeUsers);
    });


    // --- DESCONEXÃO (MANTIDO) ---
    socket.on('disconnect', () => {
        console.log(`[DESCONEXÃO] Usuário desconectado: ${currentUsername} (${socket.id})`);
        
        if (currentUsername && userMap[currentUsername] === socket.id) {
            delete userMap[currentUsername];
            console.log(`[LIMPEZA] Mapeamento de usuário removido: ${currentUsername}`);
        }

        if (currentRoom && rooms[currentRoom]) {
            if (rooms[currentRoom].sockets[socket.id]) {
                rooms[currentRoom].count--;
                delete rooms[currentRoom].sockets[socket.id];
            }
            
            if (rooms[currentRoom].count > 0 && !currentRoom.startsWith('DM_') && currentRoom !== 'Geral') {
                io.to(currentRoom).emit('chat message', {
                    user: 'Sistema', msg: `${currentUsername} desconectou.`, room: currentRoom, type: 'system'
                });
            }
        }
        updateRoomList();
    });
});


// --- INICIALIZAÇÃO DO SERVIDOR HTTP (AGORA CARREGA O HISTÓRICO) ---

// 1. Carrega o histórico para a memória antes de iniciar o servidor
roomHistory = loadHistoryFromDisk();

// 2. Inicia o servidor HTTP
http.listen(PORT, () => {
    console.log(`Servidor Node.js rodando em http://localhost:${PORT}`);
    console.log(`Histórico persistente será salvo em: ${HISTORY_FILE}`);
});