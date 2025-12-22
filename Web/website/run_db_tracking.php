<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../../config/db.php';
$pdo = sp_get_pdo();

$sql = file_get_contents(__DIR__ . '/fix_tracking.sql');

try {
    $pdo->exec($sql);
    echo "Tracking Update Applied: stats_custom_pages created & SP updated.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>