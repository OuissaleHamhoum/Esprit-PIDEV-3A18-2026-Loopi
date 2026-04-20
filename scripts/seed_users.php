<?php
require __DIR__ . '/../vendor/autoload.php';
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
$hasher = new NativePasswordHasher();
$users = [
    ['nom' => 'Admin', 'prenom' => 'System', 'email' => 'admin@loopi.tn', 'pass' => 'admin123', 'role' => 'admin'],
    ['nom' => 'Organisateur', 'prenom' => 'Eco', 'email' => 'organisateur@loopi.tn', 'pass' => 'org123', 'role' => 'organisateur'],
    ['nom' => 'Participant', 'prenom' => 'Test', 'email' => 'participant@loopi.tn', 'pass' => 'part123', 'role' => 'participant'],
];
$dbPath = realpath(__DIR__ . '/../var/data.db');
if (!$dbPath) {
    throw new RuntimeException('SQLite database file not found.');
}
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
foreach ($users as $user) {
    $hash = $hasher->hash($user['pass']);
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO users (nom, prenom, email, password, photo, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, datetime("now"), datetime("now"))');
    $stmt->execute([$user['nom'], $user['prenom'], $user['email'], $hash, 'default.jpg', $user['role']]);
}
