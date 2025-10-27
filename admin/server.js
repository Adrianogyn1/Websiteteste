const express = require('express');
const app = express();
const http = require('http');
const server = http.createServer(app);
const { Server } = require('socket.io');
const io = new Server(server);
const path = require('path');

// Servir arquivos estáticos
app.use(express.static(path.join(__dirname, 'public')));

app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// Lógica do Socket.IO (INALETRADA: Gerenciamento de conexão, salas e mensagens)
io.on('connection', (socket) => {
    console.log('Um usuário conectado com o ID:', socket.id);
    
    socket.on('join room', (roomName, username, callback) => {
        const currentRooms = Array.from(socket.rooms).filter(r => r !== socket.id);
        currentRooms.forEach(room => socket.leave(room));
        
        socket.join(roomName);
        socket.data.username = username;
        socket.data.room = roomName;
        
        console.log(`Usuário ${username} (ID: ${socket.id}) entrou na sala: ${roomName}`);
        
        socket.to(roomName).emit('chat message', {
            user: 'Sistema',
            msg: `${username} entrou na sala.`,
            room: roomName,
            type: 'system'
        });
        
        if (callback) {
            callback(roomName);
        }
    });
    
    socket.on('chat message', (msg) => {
        const room = socket.data.room;
        const user = socket.data.username || 'Anônimo';
        
        if (room) {
            const messageData = {
                user: user,
                msg: msg,
                room: room,
                type: 'user'
            };
            
            io.to(room).emit('chat message', messageData);
            console.log(`Mensagem na sala [${room}] de ${user}: ${msg}`);
        } else {
            console.log(`Mensagem de ${user} ignorada: Não está em uma sala.`);
        }
    });
    
    socket.on('disconnect', () => {
        const user = socket.data.username || 'Um usuário';
        const room = socket.data.room;
        
        if (room) {
            console.log(`Usuário ${user} desconectado da sala: ${room}`);
            io.to(room).emit('chat message', {
                user: 'Sistema',
                msg: `${user} saiu da sala.`,
                room: room,
                type: 'system'
            });
        } else {
            console.log('Usuário desconectado (sem sala)');
        }
    });
});

const PORT = 3000;
server.listen(PORT, () => {
    console.log(`Servidor rodando em http://localhost:${PORT}`);
});