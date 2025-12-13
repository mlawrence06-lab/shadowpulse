<?php
// debug_status.php
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: text/plain');

try {
    $pdo = sp_get_pdo();

    // Check Members
    $stmt = $pdo->query("SELECT count(*) FROM members");
    $memberCount = $stmt->fetchColumn();

    // Check Stats
    $stmt = $pdo->query("SELECT count(*) FROM member_stats");
    $statsCount = $stmt->fetchColumn();

    echo "DB Connection: OK\n";
    echo "Members Count: " . $memberCount . "\n";
    echo "Stats Count: " . $statsCount . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>