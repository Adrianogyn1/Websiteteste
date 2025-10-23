<?php

require_once __DIR__ . '/htdocs/app/models/DataBase.php';
require_once __DIR__ . '/htdocs/app/models/User.php';

//session_start();

/*$db = new DataBase();

$stmt = $db->query("SELECT * FROM users LIMIT 1");
$data = $stmt->fetch(PDO::FETCH_ASSOC);
$user =User::createFromArray($data);

echo $user->nome;
*/
echo "ok";

?>