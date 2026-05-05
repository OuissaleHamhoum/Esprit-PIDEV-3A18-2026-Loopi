<?php
require 'vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;
(new Dotenv())->bootEnv(__DIR__.'/.env');
$kernel = new App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$c = $kernel->getContainer();
$doctrine = $c->get('doctrine');
try {
    $repo = $doctrine->getRepository(App\Entity\Produit::class);
    $products = $repo->findAll();
    echo "Found " . count($products) . " products.\n";
    foreach ($products as $p) {
        $p->getStatus();
    }
    echo "OK getStatus()\n";
} catch (\Exception $e) {
    echo "Produit Error: " . $e->getMessage() . "\n";
}
try {
    $repo = $doctrine->getRepository(App\Entity\Feedback::class);
    $feedbacks = $repo->findAll();
    echo "Found " . count($feedbacks) . " feedbacks.\n";
} catch (\Exception $e) {
    echo "Feedback Error: " . $e->getMessage() . "\n";
}
try {
    $repo = $doctrine->getRepository(App\Entity\Favoris::class);
    $favoris = $repo->findAll();
    echo "Found " . count($favoris) . " favoris.\n";
} catch (\Exception $e) {
    echo "Favoris Error: " . $e->getMessage() . "\n";
}
