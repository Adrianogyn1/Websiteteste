<?php
ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

require_once __DIR__ . '/autoload.php';

$game = new Game();

$game->createTable();
echo "<br/>table Game ok";

$linkgame = new LinkGame();
$linkgame->createTable();
echo "<br/>table LinkGame ok";

$Provedor = new ProvedorGame();
$Provedor->createTable();
echo "<br/>table Provedor ok";

$Carteira = new Carteira();
$Carteira->createTable();
echo "<br/>table carteira ok";








/**/
echo "<br/>ok";





?>
