<?php
// Check database tables
$conn = new PDO('mysql:host=localhost;dbname=loopi_db;charset=utf8', 'root', '');

echo "Tables in loopi_db:\n";
$result = $conn->query('SHOW TABLES');
while ($row = $result->fetch(PDO::FETCH_NUM)) {
    echo $row[0] . "\n";
}

echo "\nUsers table structure:\n";
$result = $conn->query('DESCRIBE users');
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\nTesting the query:\n";
$sql = 'SELECT p.*, u.nom, u.prenom, u.email FROM participation p LEFT JOIN users u ON p.id_user = u.id WHERE p.id_evenement = ?';
$stmt = $conn->prepare($sql);
$result = $stmt->execute([1]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Query executed successfully, " . count($rows) . " rows found\n";
