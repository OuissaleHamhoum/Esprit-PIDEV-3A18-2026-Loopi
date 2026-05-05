<?php
require_once __DIR__ . '/../src/Service/BadWordFilterService.php';

$service = new App\Service\BadWordFilterService();
$text = "C'est de la merde et du f.u.c.k!";
echo "Original: $text\n";
echo "Highlighted: " . $service->highlightBadWords($text) . "\n";
echo "Is Inappropriate: " . ($service->isInappropriate($text) ? 'YES' : 'NO') . "\n";
