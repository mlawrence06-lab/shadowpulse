<?php
require_once __DIR__ . '/../../config/db.php';
$pdo = sp_get_pdo();

$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
file_put_contents('tables_output.txt', json_encode($tables));
echo "Done";
?>