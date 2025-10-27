const express = require('express');
const { createServer } = require('http');
const { Server } = require('socket.io');

const app = express();
const httpServer = createServer(app);

// --- Configuração da Porta e Limite ---
const PORT = 3010;
const HISTORY_LIMIT = 50; // Limite de rodadas para armazenar o histórico

const io = new Server(httpServer, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

// ==========================================================
// === ESTADO INTERNO DO SERVIDOR (Crescimento Dinâmico) ===
// ==========================================================

const monitoredGames = {};
// lastUpdate agora armazena um ARRAY de rodadas para cada sala.
const lastUpdate = {};

function getRoomName(gameType) {
    return gameType.toLowerCase().replace(/\s/g, '');
}

// ... (Rotas HTTP permanecem as mesmas) ...
app.get('/', (req, res) => {
    res.json({
        status: 'Online',
        port: PORT,
        message: 'Servidor Socket.IO em funcionamento. Salas aprendidas dinamicamente.',
        monitored_games: monitoredGames,
        // Adicionamos a contagem de rodadas no histórico para o endpoint de status
        last_data_by_room: Object.keys(lastUpdate).reduce((acc, room) => {
            acc[room] = {
                count: lastUpdate[room].length,
                latest: lastUpdate[room][lastUpdate[room].length - 1] || null,
                timestamp: new Date().toLocaleTimeString('pt-BR')
            };
            return acc;
        }, {})
    });
});
// ...

// ==========================================================
// === LÓGICA DO SOCKET.IO ===
// ==========================================================

io.on('connection', (socket) => {
    console.log(`\n[SERVIDOR] Novo cliente conectado: ${socket.id}`);
    
    socket.on('requestRooms', () => {
        socket.emit('availableRooms', monitoredGames);
        console.log(`[SERVIDOR] Enviada a lista de ${Object.keys(monitoredGames).length} salas para o cliente ${socket.id}.`);
    });
    
    // Evento Principal: Recebe dados do Catalogador
    socket.on('atualizacao', (data) => {
        const { gameType, novasRodadas } = data;
        
        if (!gameType || !Array.isArray(novasRodadas) || novasRodadas.length === 0) {
            console.warn(`[SERVIDOR] Dados inválidos ou incompletos recebidos do ${socket.id}.`);
            return;
        }
        
        const roomName = getRoomName(gameType);
        
        // 1. APRENDIZAGEM DINÂMICA
        if (!monitoredGames[roomName]) {
            monitoredGames[roomName] = gameType;
            // Inicializa o histórico para a nova sala
            lastUpdate[roomName] = [];
            console.log(`[SERVIDOR] NOVA SALA DETECTADA: [${roomName}] -> ${gameType}`);
            io.emit('availableRooms', monitoredGames);
        }
        
        // 2. Enviar para a sala específica (em tempo real)
        io.to(roomName).emit('novasRodadas', novasRodadas);
        
        // 3. Atualizar o cache do histórico no servidor
        const historico = lastUpdate[roomName];
        
        novasRodadas.forEach(rodada => {
            // Adiciona a nova rodada ao final do array
            historico.push(rodada);
            
            // Remove a rodada mais antiga se o limite for excedido
            if (historico.length > HISTORY_LIMIT) {
                historico.shift(); // Remove o primeiro elemento (o mais antigo)
            }
        });
        
        console.log(`[SERVIDOR] Emitido ${novasRodadas.length} rodadas para [${roomName}]. Histórico atual: ${historico.length}.`);
    });
    
    // Evento: Cliente solicitando entrar em uma sala específica (PONTO CRÍTICO)
    socket.on('joinRoom', (roomName) => {
        // Sai de todas as salas anteriores
        const roomsToLeave = Array.from(socket.rooms).filter(r => r !== socket.id);
        roomsToLeave.forEach(room => {
            socket.leave(room);
        });
        
        // Entra na nova sala
        socket.join(roomName);
        console.log(`[SERVIDOR] Cliente ${socket.id} entrou na sala: [${roomName}].`);
        
        // --- AQUI É ONDE ENVIAMOS O HISTÓRICO COMPLETO ---
        const historico = lastUpdate[roomName] || [];
        
        if (historico.length > 0) {
            console.log(`[SERVIDOR] Enviando histórico de ${historico.length} rodadas para ${socket.id}.`);
            // Envia o array completo de histórico no evento 'fullHistory'
            socket.emit('fullHistory', historico);
        } else {
            // Envia um array vazio se não houver histórico
            socket.emit('fullHistory', []);
            
            // Envia o placeholder caso o jogo já tenha sido detectado, mas não tenha resultados ainda
            if (monitoredGames[roomName]) {
                const displayName = monitoredGames[roomName];
                socket.emit('lastUpdate', { NomeDaRoleta: displayName, NumeroVencedor: 'Aguardando', DataHora: '--' });
            }
        }
    });
    
    socket.on('disconnect', () => {
        console.log(`[SERVIDOR] Cliente desconectado: ${socket.id}`);
    });
});

// ... (Inicialização do servidor) ...
httpServer.listen(PORT, () => {
    console.log(`\n--- Servidor Socket.IO Iniciado ---`);
    console.log(`Rodando em: http://localhost:${PORT}`);
    console.log(`Limite do Histórico: ${HISTORY_LIMIT} rodadas.`);
});