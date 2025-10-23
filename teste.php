<?php
ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

require_once __DIR__ . '/autoload.php';

//require_once __DIR__ . '/models/UserPdo.php';

//session_start();

/**/$db = new DataBase();
$db->getPdo()->exec("

            CREATE TABLE IF NOT EXISTS users (

                id INT AUTO_INCREMENT PRIMARY KEY,

                nome VARCHAR(100) NOT NULL,

                email VARCHAR(150) UNIQUE NOT NULL,

                senha VARCHAR(255) NOT NULL,

                criado_em DATETIME NOT NULL

            );

        ");
/**/
$novo = new User();
$novo->nome="Adriiano";
//$udb= new UserPdo();
//$udb->save($novo);
echo __DIR__;






$stmt = $db->query("SELECT * FROM users LIMIT 1");
$data = $stmt->fetch(PDO::FETCH_ASSOC);
//$user =User::createFromArray($data);


echo $data;

/**/
echo "<br/>ok";





?>
