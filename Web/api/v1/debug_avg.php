<?php
require_once __DIR__ . '/../../config/db.php';
$pdo = sp_get_pdo();

// 1. Check if column type is TINYINT
$stmt = $pdo->query("DESCRIBE votes effective_value");
$col = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Column Type: " . json_encode($col) . "\n";

// 2. Test AVG
$stmt = $pdo->query("SELECT AVG(effective_value) as val FROM votes LIMIT 1");
$res = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Simple Avg: " . json_encode($res) . "\n";

// 3. Test CAST AVG
$stmt = $pdo->query("SELECT AVG(CAST(effective_value AS DECIMAL(10,4))) as val FROM votes LIMIT 1");
$res = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Cast Avg: " . json_encode($res) . "\n";

// 4. Test actual query
$stmt = $pdo->query("SELECT target_id, AVG(effective_value) as avg_score FROM votes WHERE vote_category='topic' GROUP BY target_id LIMIT 1");
$res = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Group Avg: " . json_encode($res) . "\n";
?>