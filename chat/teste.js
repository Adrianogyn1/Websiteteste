// server.js
const express = require('express');
const app = express();

// Pega argumento da porta (ex.: node server.js 3001)
const args = process.argv.slice(2);
const port = args[0] ? parseInt(args[0], 10) : 3001; // default 3001

app.get('/', (req, res) => {
    res.send(`Servidor rodando na porta ${port}`);
});

app.listen(port, () => {
    console.log(`Servidor iniciado na porta ${port}`);
});
