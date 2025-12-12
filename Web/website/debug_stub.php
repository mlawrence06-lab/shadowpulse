<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "1. Starting debug...<br>";

echo "2. Trying to require ../api/v1/cors.php...<br>";
try {
    require_once __DIR__ . '/../api/v1/cors.php';
    echo "3. Success: cors.php included.<br>";
} catch (Throwable $e) {
    echo "3. FAIL: " . $e->getMessage() . "<br>";
}

echo "4. Trying to include header.php...<br>";
try {
    include __DIR__ . '/header.php';
    echo "5. Success: header.php included.<br>";
} catch (Throwable $e) {
    echo "5. FAIL: " . $e->getMessage() . "<br>";
}

echo "6. Debug Complete.";
?>