<?php
// Debug CLI version of search.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "1. Loading dependencies...\n";
require_once __DIR__ . '/../api/v1/cors.php';

echo "2. Hardcoding search term...\n";
$term = 'ninja';
$apiBase = 'https://api.ninjastic.space/shadowpulse';

echo "3. Building URL...\n";
$url = $apiBase . '?searchTerm=' . rawurlencode($term);
echo "   URL: $url\n";

echo "4. Executing request (curl/file_get_contents)...\n";
$errorMessage = '';
$results = [];

try {
    if (function_exists('curl_init')) {
        echo "   Using cURL...\n";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        // VERBOSE for debug
        // curl_setopt($ch, CURLOPT_VERBOSE, true);

        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errNo = curl_errno($ch);
        $errStr = curl_error($ch);
        curl_close($ch);

        echo "   HTTP Status: $status\n";
        echo "   Curl ErrNo: $errNo ($errStr)\n";
        echo "   Body Length: " . strlen($body) . "\n";

        if ($body === false || $errNo !== 0) {
            echo "   [FAIL] Curl Error.\n";
        } elseif ($status !== 200) {
            echo "   [FAIL] HTTP Error.\n";
        } else {
            echo "   [SUCCESS] API responded.\n";
            $data = json_decode($body, true);
            if (!is_array($data)) {
                echo "   [FAIL] JSON Decode error: " . json_last_error_msg() . "\n";
            } else {
                echo "   [SUCCESS] JSON Parsed. Items: " . count($data) . "\n";
            }
        }
    } else {
        echo "   Using file_get_contents...\n";
        $body = @file_get_contents($url);
        if ($body === false) {
            echo "   [FAIL] file_get_contents returned false.\n";
        } else {
            echo "   [SUCCESS] Content fetched.\n";
        }
    }
} catch (Throwable $e) {
    echo "CRITICAL EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}

echo "5. Done.\n";
?>