<?php
require 'vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;
(new Dotenv())->bootEnv(__DIR__.'/.env');
$kernel = new App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$conn = $kernel->getContainer()->get('database_connection');
try {
    $conn->executeStatement("ALTER TABLE feedback ADD status VARCHAR(20) DEFAULT 'published'");
    echo "Column added to feedback successfully.";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
