<?php
// db.php - Database Connection Helper

// Ensure CORS headers are sent for any script including this file
require_once __DIR__ . '/cors.php';

// Configuration
// UPDATED: New Host and DB Name
$SP_DB_DSN  = 'mysql:host=bxzziugsp.mysql.db;dbname=bxzziugsp;charset=utf8mb4';
// UPDATED: New User
$SP_DB_USER = 'bxzziugsp';
$SP_DB_PASS = 'dnzDu6wSs6hiomR5VTLX';

/**
 * Public: Get shared PDO instance
 */
function sp_get_pdo() {
    static $pdo = null;
    global $SP_DB_DSN, $SP_DB_USER, $SP_DB_PASS;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                $SP_DB_DSN, 
                $SP_DB_USER, 
                $SP_DB_PASS, 
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (Throwable $e) {
            // If connection fails, return JSON error immediately
            if (!headers_sent()) {
                header('Content-Type: application/json');
                http_response_code(500);
            }
            echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }
    }

    return $pdo;
}
?>