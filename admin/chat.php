<?php
$port = 3000;
// =======================================================
// Lógica de Controle do Servidor (PHP)
// =======================================================

// LIGAR
if(isset($_POST["ligar"])){
    $script = __DIR__ . '/server.js';
    // Comando para rodar em segundo plano e retornar o PID
    $command = "node {$script} > /dev/null 2>&1 & echo $!";
    $pid = shell_exec($command);
    echo "Servidor ligado! PID: " . trim($pid);
    exit;
}

// DESLIGAR
if(isset($_POST["desligar"])){
    
    
    // 1. Tenta encontrar o PID que está usando a porta 3000
    // lsof -t -i :3000: Retorna apenas o PID da porta
    // 2>&1 redireciona erros para a saída padrão
    $pid_to_kill = shell_exec("lsof -t -i :{$port} 2>/dev/null");
    $pid_to_kill = trim($pid_to_kill); // Remove espaços em branco

    if (!empty($pid_to_kill)) {
        // 2. Mata o processo pelo PID encontrado (usa sudo se configurado)
        // O comando 'kill -9' é o mais forçado
        shell_exec("sudo kill -9 {$pid_to_kill} 2>&1");
        
        // Agora, tenta uma limpeza adicional para processos 'node server.js' caso
        // haja algum processo filho ou parente restante que lsof não pegou.
        shell_exec("sudo pkill -9 -f 'node' 2>&1");
        
        echo "Servidor desligado com sucesso! PID(s) encerrado(s): " . $pid_to_kill;
        
    } else {
        // Se lsof não achou PID na porta 3000, o servidor já estava (ou parecia) parado.
        // Tenta um pkill de fallback para garantir
        $pkill_result = shell_exec("sudo pkill -9 -f 'node server.js' 2>&1");
        
        if (empty($pkill_result)) {
             echo "Servidor não estava rodando na porta 3000 (ou foi desligado via fallback).";
        } else {
             echo "Erro ao desligar o servidor via lsof e pkill: " . $pkill_result;
        }
    }
    exit;
}

//// VERIFICAR STATUS
if(isset($_POST["status"])){
    

    // Tenta encontrar o PID que está usando a porta 3000
    $pid_running = shell_exec("lsof -t -i :{$port} 2>/dev/null");
    $pid_running = trim($pid_running);

    if (!empty($pid_running)) {
        // Porta em uso, então o servidor está ativo.
        echo "rodando | PID: " . $pid_running;
    } else {
        // Porta não em uso, então o servidor está parado.
        echo "parado";
    }
    exit; 
}

?>

