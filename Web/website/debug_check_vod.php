<?php
// debug_check_vod.php
require_once __DIR__ . '/../../config/db.php';

try {
    $pdo = sp_get_pdo();
    $stmt = $pdo->prepare("SELECT member_id, custom_name, member_uuid FROM members WHERE custom_name = ?");
    $stmt->execute(['vod']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: text/plain');
    if ($row) {
        echo "TAKEN by Member ID: " . $row['member_id'];
    } else {
        echo "AVAILABLE";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
