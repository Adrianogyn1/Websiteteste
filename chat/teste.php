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

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Teste Node com PHP</title>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
#status { border: 1px solid #ccc; padding: 10px; margin-top: 10px; height: 150px; overflow-y: auto; }
</style>
</head>
<body>

<h2>Controle do Node.js</h2>

<label for="porta">Porta:</label>
<input type="number" id="porta" value="3001" />

<button id="ligar">Ligar</button>
<button id="desligar">Desligar</button>

<div id="status"></div>

<script>
function addStatus(msg, color='green'){
    $('#status').append('<div style="color:'+color+';">'+msg+'</div>');
    $('#status').scrollTop($('#status')[0].scrollHeight);
}

$('#ligar').click(function(){
    let porta = $('#porta').val();
    if(!porta) porta = 3001;

    $.post('', {ligarteste: true, porta: porta}, function(data){
        addStatus(data, 'green');
    });
});

$('#desligar').click(function(){
    $.post('', {desligarteste: true}, function(data){
        addStatus(data, 'red');
    });
});
</script>

</body>
</html>
