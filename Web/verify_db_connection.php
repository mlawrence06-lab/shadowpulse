<?php
// verify_db_connection.php
require_once __DIR__ . '/config/db.php';

try {
    $pdo = sp_get_pdo();
    echo "<h1>Database Connection Successful!</h1>";
    echo "<p>Host: " . 'bxzziugsp.mysql.db' . "</p>";
    echo "<p>Database: " . 'bxzziugsp' . "</p>";
    echo "<p>User: " . 'bxzziugsp' . "</p>";
} catch (Exception $e) {
    echo "<h1>Database Connection Failed</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
