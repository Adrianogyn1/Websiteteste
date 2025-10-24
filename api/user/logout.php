<?php

require_once(dirname(__DIR__, 2) . '/autoload.php');
session_start();

$msg = new ApiMessage(); // inicializa padrão: sucess=false, msg='', data=null
$msg->msg = "Deslogado com sucesso.";
            

session_destroy();

$msg->toJson();

?>
