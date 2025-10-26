<?php
// PHP responsável por iniciar/desligar o Node
if(isset($_POST["ligarteste"])){

    $script = __DIR__ . '/teste.js';
    $porta = isset($_POST['porta']) ? (int)$_POST['porta'] : 3001;

    $output = shell_exec('node '.escapeshellarg($script).' '.escapeshellarg($porta).' 2>&1 & echo $!');
    echo $output ?: "Servidor iniciado na porta $porta!";
    exit;
}

if(isset($_POST["desligarteste"])){
    // Finaliza processos Node que rodem teste.js
    $output = shell_exec("pkill -f 'teste.js' 2>&1");
    echo $output ?: "Servidor desligado! 😅";
    exit;
}
?>

