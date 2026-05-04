<?php
require 'vendor/autoload.php';
use Symfony\Component\HttpFoundation\Request;
use App\Kernel;

$kernel = new Kernel('dev', true);
$request = Request::create('/organisateur');
$response = $kernel->handle($request);
echo 'Status: ' . $response->getStatusCode() . PHP_EOL;
echo 'Content length: ' . strlen($response->getContent()) . PHP_EOL;
echo 'Content preview: ' . substr($response->getContent(), 0, 200) . PHP_EOL;
?>