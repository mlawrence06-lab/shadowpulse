<?php
// dump_schema.php
header('Content-Type: text/plain');
require __DIR__ . '/../../config/db.php';

try {
    $pdo = sp_get_pdo();
    $stmt = $pdo->query("SHOW CREATE TABLE member_stats");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $row['Create Table'];
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
