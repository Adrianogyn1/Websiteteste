const axios = require('axios');
const io = require('socket.io-client');

// ===============================================
// === AJUSTES DE CONFIGURAÇÃO DO CATALOGADOR ===

// 1. URL do seu servidor Socket.IO (Porta 3010)
const SOCKET_IO_SERVER = 'http://localhost:3010';

// 2. Intervalo de busca (em segundos)
const INTERVALO_SEGUNDOS = 5;

// 3. Estrutura de configuração para as APIs monitoradas
// O 'lastId' será atualizado e mantido em memória pelo script.
const MONITOR_CONFIG = [
    {
        gameType: 'Auto Roulette',
        url: 'https://api.casinoscores.com/svc-evolution-game-events/api/autoroulette?page=0&size=10&sort=data.settledAt,desc&duration=6',
        lastId: null
    },
    {
        gameType: 'Immersive Roulette',
        url: 'https://api.casinoscores.com/svc-evolution-game-events/api/immersiveroulette?page=0&size=10&sort=data.settledAt,desc&duration=6',
        lastId: null
    },
    // Você pode adicionar mais roletas aqui (ex: Lightning Roulette), 
    // e o servidor irá detectá-las automaticamente.
];

// ===============================================

const socket = io(SOCKET_IO_SERVER);

socket.on('connect', () => {
    console.log(`\n[CATALOGADOR] Conectado ao servidor Socket.IO: ${socket.id}`);
});

socket.on('connect_error', (err) => {
    console.error(`\n[CATALOGADOR] ERRO de conexão com o Socket.IO: ${err.message}.`);
});

/**
 * Filtra os resultados para encontrar rodadas novas e extrai os dados essenciais.
 * @param {Array} resultados - O array de objetos de resultados da API (bruto).
 * @param {string | null} lastId - O último ID processado para este tipo de jogo.
 * @returns {{novasRodadas: Array, novoLastId: string | null}}
 */
function filtrarEExtrairDados(resultados, lastId) {
    const novasRodadas = [];
    let novoLastId = lastId;
    
    // Invertemos para processar do MAIS ANTIGO para o MAIS NOVO (garante a ordem cronológica)
    const resultadosOrdenados = [...resultados].reverse();
    
    for (const rodada of resultadosOrdenados) {
        if (rodada.id === lastId) {
            // Chegamos ao último ID que já processamos.
            break;
        }
        
        // Extração dos dados essenciais
        const numeroVencedor = rodada.data.result.outcome.number;
        const nomeRoleta = rodada.data.table.name;
        const dataHora = new Date(rodada.data.settledAt).toLocaleString('pt-BR');
        
        novasRodadas.push({
            id: rodada.id, // ID é crucial para o controle de histórico
            NomeDaRoleta: nomeRoleta,
            DataHora: dataHora,
            NumeroVencedor: numeroVencedor
        });
        
        // Na primeira execução, define o ID mais antigo da lista como ponto de partida
        if (!novoLastId) {
            novoLastId = rodada.id;
        }
    }
    
    // Se novas rodadas foram encontradas, o ID da rodada mais recente é o primeiro item do array original (descendente)
    if (novasRodadas.length > 0) {
        novoLastId = resultados[0].id;
    }
    
    // O array 'novasRodadas' está em ordem cronológica (antigo -> novo), ideal para processamento em fila no servidor
    return {
        novasRodadas: novasRodadas,
        novoLastId: novoLastId
    };
}


/**
 * Executa a busca e atualização para UMA ÚNICA configuração de monitoramento.
 * @param {Object} monitor - O objeto de configuração da API.
 */
async function processarMonitor(monitor) {
    const { gameType, url, lastId } = monitor;
    
    let resultadosBrutos;
    try {
        const response = await axios.get(url);
        
        // As APIs da casinoscores geralmente retornam um objeto com a chave 'content' que contém o array de resultados.
        const responseData = response.data.content || response.data;
        
        if (response.status !== 200 || !Array.isArray(responseData)) {
            // Se o retorno não for um array ou não tiver status 200, lançamos um erro.
            throw new Error(`Resposta inválida da API (Status: ${response.status} ou formato incorreto).`);
        }
        resultadosBrutos = responseData;
        
    } catch (error) {
        console.error(`\n!!! ERRO ao buscar dados de [${gameType}] !!!`);
        console.error(`Detalhes: ${error.message}`);
        return;
    }
    
    // 1. Filtrar e extrair apenas os resultados NOVOS
    const { novasRodadas, novoLastId } = filtrarEExtrairDados(resultadosBrutos, lastId);
    
    if (novasRodadas.length > 0) {
        console.log(`\n[${gameType}] >>> ${novasRodadas.length} novas rodadas encontradas!`);
        
        // 2. Enviar os novos resultados via Socket.IO
        if (socket.connected) {
            // Emite o evento 'atualizacao' contendo o gameType (para o servidor criar/selecionar a sala) e os dados.
            socket.emit('atualizacao', {
                gameType: gameType,
                novasRodadas: novasRodadas
            });
            console.log(`[${gameType}] Dados emitidos para o Servidor com sucesso.`);
        } else {
            console.warn(`[${gameType}] Socket.IO não conectado. Dados não enviados.`);
        }
        
        // 3. Atualizar o histórico no objeto de configuração SOMENTE após a busca ser bem-sucedida.
        monitor.lastId = novoLastId;
        console.log(`[${gameType}] Histórico atualizado para o ID: ${monitor.lastId}`);
    } else {
        // console.log(`[${gameType}] Nenhuma nova rodada. Esperando...`);
    }
}


/**
 * Função principal que itera sobre todas as APIs e inicia o processamento.
 */
async function iniciarCatalogador() {
    const timestamp = new Date().toLocaleTimeString('pt-BR');
    console.log(`\n--- [${timestamp}] Executando o Catalogador em ${MONITOR_CONFIG.length} fontes... ---`);
    
    // Usa Promise.all para executar todas as buscas HTTP em paralelo, ganhando velocidade.
    await Promise.all(MONITOR_CONFIG.map(processarMonitor));
}

// ----------------------------------------------------
// --- Início do Loop de Atualização ---

// Executa a busca imediatamente
iniciarCatalogador();

// Configura a execução repetida a cada X segundos
const intervaloMs = INTERVALO_SEGUNDOS * 1000;
setInterval(iniciarCatalogador, intervaloMs);

console.log(`\nMonitoramento iniciado. Buscando a cada ${INTERVALO_SEGUNDOS} segundos...`);
console.log(`Conectando em: ${SOCKET_IO_SERVER}`);
console.log('Pressione Ctrl+C para parar o script.');