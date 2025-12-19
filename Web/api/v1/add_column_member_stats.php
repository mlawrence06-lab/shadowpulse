<?php
// add_column_member_stats.php
// Fixes 500 Error by ensuring member_stats has page_views column.

header('Content-Type: text/plain');
require __DIR__ . '/../../config/db.php';

try {
    $pdo = sp_get_pdo();
    echo "Connected.\n";

    // Add page_views to member_stats
    // Use try-catch to ignore duplicate column error
    try {
        $pdo->exec("ALTER TABLE member_stats ADD COLUMN page_views BIGINT UNSIGNED DEFAULT 0");
        echo "Updated member_stats.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), '1060') !== false || strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Column already exists in member_stats.\n";
        } else {
            echo "Error updating member_stats: " . $e->getMessage() . "\n";
        }
    }

} catch (PDOException $e) {
    echo "FATAL: " . $e->getMessage();
}
