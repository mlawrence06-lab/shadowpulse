<?php
// db.php - Database Connection Helper

// Ensure CORS headers are sent for any script including this file
require_once __DIR__ . '/cors.php';

// Configuration
$SP_DB_DSN  = 'mysql:host=martinsays.workisboring.com;dbname=shadowpulse;charset=utf8mb4';
$SP_DB_USER = 'shadowpulse_app';
$SP_DB_PASS = 'dnzDu6wSs6hiomR5VTLX';

// Retry Config
$SP_DB_MAX_RETRIES    = 3;
$SP_DB_RETRY_DELAY_MS = 1500;

/**
 * Internal: Create PDO with retry logic
 */
function sp_create_pdo_with_retry() {
    global $SP_DB_DSN, $SP_DB_USER, $SP_DB_PASS, $SP_DB_MAX_RETRIES, $SP_DB_RETRY_DELAY_MS;

    $attempt = 0;
    $lastException = null;

    while ($attempt < $SP_DB_MAX_RETRIES) {
        $attempt++;
        try {
            $pdo = new PDO(
                $SP_DB_DSN, 
                $SP_DB_USER, 
                $SP_DB_PASS, 
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5,
                ]
            );
            return $pdo;
        } catch (Throwable $e) {
            $lastException = $e;
            // Only log if necessary to avoid filling disk
            // error_log("DB Connect failed attempt $attempt: " . $e->getMessage());
            if ($attempt < $SP_DB_MAX_RETRIES) {
                usleep($SP_DB_RETRY_DELAY_MS * 1000);
            }
        }
    }
    
    throw $lastException;
}

/**
 * Public: Get shared PDO instance
 */
function sp_get_pdo() {
    static $pdo = null;

    if ($pdo === null) {
        $pdo = sp_create_pdo_with_retry();
        return $pdo;
    }

    // Ping check
    try {
        $pdo->query("SELECT 1");
    } catch (Throwable $e) {
        $pdo = sp_create_pdo_with_retry();
    }

    return $pdo;
}