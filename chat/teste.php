

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

    $.post('http://35.209.27.45/admin/teste.php', {ligarteste: true, porta: porta}, function(data){
        addStatus(data, 'green');
    });
});

$('#desligar').click(function(){
    $.post('http://35.209.27.45/app/admin/teste.php', {desligarteste: true}, function(data){
        addStatus(data, 'red');
    });
});
</script>

</body>
</html>
