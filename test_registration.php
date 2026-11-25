<?php

$ch = curl_init('http://127.0.0.1:8000/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);

// Extract cookies
preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
$cookies = array();
foreach($matches[1] as $item) {
    parse_str($item, $cookie);
    $cookies = array_merge($cookies, $cookie);
}
$cookieString = '';
foreach ($cookies as $k => $v) {
    $cookieString .= "$k=$v; ";
}

// Extract CSRF token
preg_match('/<input type="hidden" name="_token" value="([^"]+)">/', $response, $tokenMatch);
$csrfToken = $tokenMatch[1] ?? '';

if (!$csrfToken) {
    echo "CSRF token not found\n";
    exit(1);
}

// Now submit POST request
$postData = [
    '_token' => $csrfToken,
    'role' => 'restaurant',
    'name' => 'Test User',
    'email' => 'testuser' . rand(1000, 9999) . '@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'phone' => '1234567890',
    'address' => 'Adajan',
    'city' => 'Surat',
    'state' => 'Gujarat',
    'pincode' => '395009',
    'restaurant_name' => 'Test Restaurant',
    'owner_name' => 'Test Owner',
    'license_number' => '12345',
    'opening_time' => '09:00',
    'closing_time' => '22:00'
];

$ch2 = curl_init('http://127.0.0.1:8000/register');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Cookie: ' . $cookieString,
    'Accept: application/json'
]);

$response2 = curl_exec($ch2);
$httpcode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

echo "HTTP Code: $httpcode\n";
echo "Response: $response2\n";
