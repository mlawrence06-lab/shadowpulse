<?php
// debug_schema.php
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: text/plain');

try {
    $pdo = sp_get_pdo();
    $stmt = $pdo->query("SHOW COLUMNS FROM members");
    $cols = $stmt->fetchAll(PDO::FETCH_Column);
    echo "Columns: " . implode(", ", $cols);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>