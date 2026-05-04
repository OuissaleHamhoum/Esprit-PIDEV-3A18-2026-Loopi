<?php
// Quick script to create a test user with proper password hashing

require_once 'vendor/autoload.php';
require_once 'config/bootstrap.php';

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

// Get Doctrine EntityManager
$entityManager = require_once 'config/bootstrap.php';
$doctrine = new \Doctrine\ORM\EntityManager(
    $GLOBALS['kernel']->getContainer()->get('doctrine.orm.default_entity_manager')
);

// Create test user
$user = new User();
$user->setNom('Test');
$user->setPrenom('User');
$user->setEmail('testuser_correct@loopi.tn');
$user->setPassword('$2y$13$vpzGhBwiBIyxTZ4M3AxgAeeOueds6H7E6mSuxY0uIjVNUc0r/lHWu'); // password 'password'
$user->setPhoto('default.jpg');
$user->setRole('participant');
$user->setCreatedAt(new \DateTime());
$user->setUpdatedAt(new \DateTime());

$entityManager->persist($user);
$entityManager->flush();

echo "Test user created successfully\n";
