<?php

// Script to update existing events with statut_validation
require_once __DIR__ . '/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

$connectionParams = [
    'dbname' => 'loopi_db',
    'user' => 'root',
    'password' => '',
    'host' => 'localhost',
    'driver' => 'pdo_mysql',
];

$conn = DriverManager::getConnection($connectionParams);

// Update events that don't have statut_validation set
$sql = "UPDATE evenement SET statut_validation = 'valide' WHERE statut_validation IS NULL OR statut_validation = ''";
$stmt = $conn->prepare($sql);
$result = $stmt->executeQuery();

echo "Updated " . $result->rowCount() . " events\n";

// Also update statut if it's null
$sql2 = "UPDATE evenement SET statut = 'en_attente' WHERE statut IS NULL OR statut = ''";
$stmt2 = $conn->prepare($sql2);
$result2 = $stmt2->executeQuery();

echo "Updated " . $result2->rowCount() . " event statuses\n";

echo "Done!\n";