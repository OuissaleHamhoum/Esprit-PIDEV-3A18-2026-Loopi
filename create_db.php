<?php
// Create database connection
$host = 'localhost';
$user = 'root';
$password = '';  // Usually empty for XAMPP

// Connect to MySQL without specifying database
try {
    $conn = new PDO("mysql:host=$host", $user, $password);
    echo "✓ Connected to MySQL/MariaDB\n";
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS loopi_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;";
    $conn->exec($sql);
    echo "✓ Database 'loopi_db' created successfully\n";
    
    // Now connect to the new database
    $conn = new PDO("mysql:host=$host;dbname=loopi_db", $user, $password);
    echo "✓ Connected to loopi_db\n";
    
    // Read and execute loopi.sql
    $sql_file = file_get_contents('loopi.sql');
    
    // Split by GO or semicolon (for multiple statements)
    $statements = array_filter(array_map('trim', preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql_file)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $conn->exec($statement);
            } catch (Exception $e) {
                echo "⚠ Statement error (continuing): " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "✓ Database initialized successfully!\n";
    
    // Verify tables
    $result = $conn->query("SHOW TABLES;");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    echo "\n✓ Tables created (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
    // Count users
    try {
        $result = $conn->query("SELECT COUNT(*) as count FROM user");
        $row = $result->fetch(PDO::FETCH_ASSOC);
        echo "\n✓ Users in database: " . $row['count'] . "\n";
    } catch (Exception $e) {
        echo "Note: Could not count users yet\n";
    }
    
} catch(Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
