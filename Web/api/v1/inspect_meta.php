<?php
require_once __DIR__ . '/../../config/db.php';
$pdo = sp_get_pdo();

echo "--- COLUMNS ---\n";
$stmt = $pdo->query("DESCRIBE content_metadata");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($cols) . "\n\n";

echo "--- DATA SAMPLE ---\n";
$stmt = $pdo->query("SELECT * FROM content_metadata LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows) . "\n";
?>