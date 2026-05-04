<?php

// Test show event endpoint
echo "=== Testing Show Event Endpoint ===\n";
$ch = curl_init('http://localhost:8000/api/admin/events/1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $httpCode\n";
$body = substr($response, strpos($response, "\r\n\r\n") + 4);
echo "Response: " . substr($body, 0, 300) . "...\n\n";

// Test create event with image
echo "=== Testing Create Event with Image ===\n";

// Create a minimal valid JPEG image (1x1 pixel black square)
$imageData = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/AB//2Q==');

$imagePath = __DIR__ . '/test_image.jpg';
file_put_contents($imagePath, $imageData);

if (file_exists($imagePath)) {
    $ch = curl_init('http://localhost:8000/api/admin/events');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_POST, true);

    $postData = [
        'titre' => 'Test Event with Image',
        'description' => 'Testing image upload',
        'date_evenement' => '2026-05-01',
        'heure_evenement' => '10:00',
        'lieu' => 'Test Location',
        'id_organisateur' => '2',
        'capacite_max' => '20'
    ];

    $boundary = '----FormBoundary' . uniqid();
    $body = '';

    foreach ($postData as $key => $value) {
        $body .= "--$boundary\r\n";
        $body .= "Content-Disposition: form-data; name=\"$key\"\r\n\r\n";
        $body .= "$value\r\n";
    }

    $body .= "--$boundary\r\n";
    $body .= "Content-Disposition: form-data; name=\"image_evenement\"; filename=\"test.jpg\"\r\n";
    $body .= "Content-Type: image/jpeg\r\n\r\n";
    $body .= file_get_contents($imagePath) . "\r\n";
    $body .= "--$boundary--\r\n";

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: multipart/form-data; boundary=' . $boundary,
        'Content-Length: ' . strlen($body)
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Status: $httpCode\n";
    $body = substr($response, strpos($response, "\r\n\r\n") + 4);
    echo "Response: " . substr($body, 0, 300) . "...\n\n";
} else {
    echo "Test image not found\n";
}

echo "=== Done ===\n";