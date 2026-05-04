<?php
// Test direct login - first get CSRF token
$ch = curl_init('http://127.0.0.1:8000/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');

$page = curl_exec($ch);
curl_close($ch);

// Extract CSRF token from the page
preg_match('/data-csrf="([^"]+)"/', $page, $matches);
$csrfToken = $matches[1] ?? 'not_found';

echo "CSRF Token: $csrfToken\n";

// Now try login
$data = [
    '_username' => 'admin@loopi.tn',
    '_password' => 'admin123',
    '_csrf_token' => $csrfToken
];

$ch = curl_init('http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
curl_setopt($ch, CURLOPT_HEADER, true); // Include headers

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

echo "Login HTTP Code: $httpCode\n";
echo "Login Final URL: $finalUrl\n";

// Extract redirect location
preg_match('/Location: ([^\r\n]+)/', $response, $locationMatches);
$redirectUrl = $locationMatches[1] ?? 'none';

echo "Redirect URL: $redirectUrl\n";

// Now try to access admin page with the same session
$ch = curl_init('http://127.0.0.1:8000/admin');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');

$adminResponse = curl_exec($ch);
$adminHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Admin HTTP Code: $adminHttpCode\n";
if ($adminHttpCode == 200 && strpos($adminResponse, 'admin') !== false) {
    echo "SUCCESS: Admin page loaded!\n";
} elseif ($adminHttpCode == 302) {
    echo "REDIRECT: Admin access denied, redirected to login\n";
} else {
    echo "UNKNOWN: Admin page response code $adminHttpCode\n";
}
?>