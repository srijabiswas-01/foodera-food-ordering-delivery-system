<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(array('error' => 'Sign in to use current location.'));
    exit;
}

$latitude = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
$longitude = filter_input(INPUT_GET, 'lon', FILTER_VALIDATE_FLOAT);
if ($latitude === false || $longitude === false || $latitude === null || $longitude === null || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    http_response_code(422);
    echo json_encode(array('error' => 'The location coordinates are invalid.'));
    exit;
}

$lastLookup = (int) ($_SESSION['location_lookup_at'] ?? 0);
if ($lastLookup && time() - $lastLookup < 2) {
    http_response_code(429);
    echo json_encode(array('error' => 'Please wait a moment before checking location again.'));
    exit;
}
$_SESSION['location_lookup_at'] = time();

$url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&zoom=18&lat=' . rawurlencode((string) $latitude) . '&lon=' . rawurlencode((string) $longitude);
function locationLookupRequest($url)
{
    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => array('Accept: application/json', 'User-Agent: Foodera/1.0 (delivery address lookup)')
        ));
        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        return $status >= 200 && $status < 300 ? $response : false;
    }

    $context = stream_context_create(array('http' => array(
        'method' => 'GET',
        'timeout' => 12,
        'header' => "Accept: application/json\r\nUser-Agent: Foodera/1.0 (delivery address lookup)\r\n"
    )));
    return @file_get_contents($url, false, $context);
}

$response = locationLookupRequest($url);
$decoded = $response ? json_decode($response, true) : null;
if (!is_array($decoded) || empty($decoded['display_name'])) {
    http_response_code(502);
    echo json_encode(array('error' => 'The address lookup service is unavailable. Try again or enter your address manually.'));
    exit;
}

echo json_encode(array('address' => $decoded['display_name'], 'latitude' => $latitude, 'longitude' => $longitude));
