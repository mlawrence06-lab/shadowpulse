<?php
require_once __DIR__ . '/../../config/db.php';
$pdo = sp_get_pdo();

// Allow raw SELECT for debugging
$sql = file_get_contents('php://input');
if (!$sql)
    die("No SQL");

try {
    if (stripos(trim($sql), 'SELECT') === 0) {
        $stmt = $pdo->query($sql);
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($res);
    } else {
        $pdo->exec($sql);
        echo "Executed";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>