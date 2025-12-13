<?php
require_once __DIR__ . '/../../config/db.php';
$pdo = sp_get_pdo();

$stmt = $pdo->query("DESCRIBE content_metadata");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($columns);
?>