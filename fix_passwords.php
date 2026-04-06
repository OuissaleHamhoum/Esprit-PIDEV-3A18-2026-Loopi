<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=loopi_db;charset=utf8', 'root', '');
$stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email IN (?, ?, ?)');
$hash = '$2y$13$vpzGhBwiBIyxTZ4M3AxgAeeOueds6H7E6mSuxY0uIjVNUc0r/lHWu';
$stmt->execute([$hash, 'admin@loopi.tn', 'organisateur@loopi.tn', 'participant@loopi.tn']);
echo $stmt->rowCount() . " rows updated\n";
