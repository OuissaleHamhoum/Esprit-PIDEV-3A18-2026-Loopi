<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=loopi_db;charset=utf8', 'root', '');
$stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
$stmt->execute(['admin123', 'admin@loopi.tn']);
$stmt->execute(['org123', 'organisateur@loopi.tn']);
$stmt->execute(['part123', 'participant@loopi.tn']);
echo $stmt->rowCount() . " rows affected\n";
