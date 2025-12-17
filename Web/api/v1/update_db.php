<?php
// update_db.php - Generic SQL Runner
require __DIR__ . '/../../config/db.php';
$pdo = sp_get_pdo();

// Load clean_summary_v2.sql
$files = [
    __DIR__ . '/../../database/clean_summary_v2.sql'
];

foreach ($files as $file) {
    echo "Processing " . basename($file) . "... ";
    if (!file_exists($file)) {
        echo "File not found.<br>";
        continue;
    }
    $sql = file_get_contents($file);
    try {
        $pdo->exec($sql);
        echo "Success.<br>";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
}
?>