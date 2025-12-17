<?php
// update_db_root.php - Run SQL updates from root
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/config/db.php';
$pdo = sp_get_pdo();

$sql = file_get_contents(__DIR__ . '/create_sp_context.sql');

if (!$sql) {
    die("Error reading SQL file.");
}

try {
    $pdo->exec($sql);
    echo "Stored Procedure Created Successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>