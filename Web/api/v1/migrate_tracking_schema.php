<?php
// migrate_tracking_schema.php
// Adds page_views column to stats_boards if it doesn't exist.

require __DIR__ . '/../../config/db.php';
header('Content-Type: text/plain');

try {
    $pdo = sp_get_pdo();
    echo "Migrating Schema for Board View Tracking...\n";

    // 1. Check if column exists
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'stats_boards' 
        AND COLUMN_NAME = 'page_views'
    ");
    $stmt->execute();
    $exists = $stmt->fetchColumn();

    if (!$exists) {
        echo "Adding page_views to stats_boards...\n";
        $pdo->exec("ALTER TABLE stats_boards ADD COLUMN page_views BIGINT UNSIGNED DEFAULT 0");
        echo "Column added.\n";
    } else {
        echo "Column page_views already exists in stats_boards.\n";
    }

    echo "Migration Complete.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    http_response_code(500);
}
?>