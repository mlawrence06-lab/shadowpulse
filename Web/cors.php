<?php
// cors.php - Shared CORS headers
// UPDATED: Using Wildcard (*) because ShadowPulse does not use cookies.
// This guarantees headers are sent regardless of server configuration.

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Handle preflight requests immediately
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}