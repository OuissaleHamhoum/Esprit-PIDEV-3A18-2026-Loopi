<?php

// Test health endpoint
echo "=== Testing Health Endpoint ===\n";
$ch = curl_init('http://localhost:8000/api/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $httpCode\n";
echo "Response: " . substr($response, 0, 500) . "\n\n";

// Test admin events endpoint (will redirect to login)
echo "=== Testing Admin Events Endpoint (without auth) ===\n";
$ch = curl_init('http://localhost:8000/api/admin/events');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $httpCode\n";
echo "Response: " . substr($response, 0, 500) . "\n\n";

// Test admin events show endpoint (will redirect to login)
echo "=== Testing Admin Events Show Endpoint (without auth) ===\n";
$ch = curl_init('http://localhost:8000/api/admin/events/1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $httpCode\n";
echo "Response: " . substr($response, 0, 500) . "\n\n";

echo "=== Testing Admin Events Create Endpoint (without auth) ===\n";
$ch = curl_init('http://localhost:8000/api/admin/events');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'titre' => 'Test Event',
    'description' => 'Test description',
    'date_evenement' => '2026-04-25',
    'heure_evenement' => '14:00',
    'lieu' => 'Test Location',
    'id_organisateur' => 1,
    'capacite_max' => 50
]));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $httpCode\n";
echo "Response: " . substr($response, 0, 500) . "\n\n";