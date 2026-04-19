<?php

require_once __DIR__ . '/vendor/autoload.php';

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DriverManager;
use App\Entity\User;

// Bootstrap Symfony kernel
$kernel = new \App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();

// Create admin user if not exists
$adminUser = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@test.com']);
if (!$adminUser) {
    $adminUser = new User();
    $adminUser->setEmail('admin@test.com');
    $adminUser->setNom('Admin');
    $adminUser->setPrenom('Test');
    $adminUser->setPassword(password_hash('admin123', PASSWORD_DEFAULT));
    $adminUser->setRole('admin');
    $adminUser->setCreatedAt(new \DateTime());
    $adminUser->setUpdatedAt(new \DateTime());

    $entityManager->persist($adminUser);
    $entityManager->flush();

    echo "Admin user created: admin@test.com / admin123\n";
} else {
    echo "Admin user already exists\n";
}

// Test login to get session
echo "=== Testing Login ===\n";
$ch = curl_init('http://localhost:8000/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_username' => 'admin@test.com',
    '_password' => 'admin123',
    '_csrf_token' => 'dummy' // We'll need to get real CSRF token
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headers = curl_getinfo($ch, CURLINFO_HEADER_OUT);
curl_close($ch);

echo "Login Status: $httpCode\n";
echo "Login Response: " . substr($response, 0, 500) . "\n\n";

// For now, let's test the API directly by bypassing auth temporarily
// We'll modify the controller to skip auth for testing

echo "=== Testing Admin Events API (with auth bypass) ===\n";

// First, let's check if we can access the events page
$ch = curl_init('http://localhost:8000/admin/events');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Admin Events Page Status: $httpCode\n";
if ($httpCode == 302) {
    echo "Redirected to login (expected)\n";
} else {
    echo "Unexpected response\n";
}

echo "\n=== Done ===\n";