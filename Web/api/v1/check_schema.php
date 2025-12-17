<?php
// check_schema.php
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: text/plain');

try {
    $pdo = sp_get_pdo();
    echo "--- MEMBERS TABLE SCHEMA ---\n";
    $stmt = $pdo->query("DESCRIBE members");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>