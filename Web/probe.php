<?php
// probe.php - Simple CORS and Error Test

// 1. Manually send CORS headers (No include files)
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin === 'https://bitcointalk.org' || $origin === 'https://www.bitcointalk.org') {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// 2. Output simple JSON
echo json_encode(['status' => 'ok', 'message' => 'PHP is working']);