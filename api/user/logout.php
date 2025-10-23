<?php
session_start();
session_destroy();
//header('Location: login.php');
echo json_encode(['ok' => true]);
exit;

?>
