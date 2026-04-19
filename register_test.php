<?php
$url = 'http://localhost:8000/register';
$data = http_build_query([
    'email' => 'test.organisateur@example.com',
    'password' => 'Secret123',
    'nom' => 'Test',
    'prenom' => 'Organisateur',
    'role' => 'organisateur'
]);
$opts = [
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                    "Content-Length: " . strlen($data) . "\r\n",
        'content' => $data,
        'ignore_errors' => true,
        'follow_location' => false,
    ]
];
$ctx = stream_context_create($opts);
$r = file_get_contents($url, false, $ctx);
echo "Status: " . substr($http_response_header[0], 9) . PHP_EOL;
print_r($http_response_header);
echo "Body length: " . strlen($r) . PHP_EOL;
