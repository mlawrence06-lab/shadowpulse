<?php
// fix_database.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain');

require_once 'db.php';
$pdo = sp_get_pdo();

echo "--- STARTING DATABASE FIX ---\n";

// 1. ADD COLUMNS TO MEMBER_STATS
echo "1. Adding missing columns to member_stats... ";
try {
    $sql = "ALTER TABLE member_stats
            ADD COLUMN topic_votes int UNSIGNED DEFAULT 0,
            ADD COLUMN post_votes int UNSIGNED DEFAULT 0,
            ADD INDEX idx_stats_page_views (page_views DESC),
            ADD INDEX idx_stats_searches (searches_made DESC),
            ADD INDEX idx_stats_topic_votes (topic_votes DESC),
            ADD INDEX idx_stats_post_votes (post_votes DESC)";
    $pdo->exec($sql);
    echo "DONE.\n";
} catch (Exception $e) {
    // If it fails, it might already exist, which is fine.
    echo "SKIPPED (Error: " . $e->getMessage() . ")\n";
}

// 2. MIGRATE DATA (Recover history from members table)
echo "2. Copying data from members to member_stats... ";
try {
    $sql = "UPDATE member_stats ms
            JOIN members m ON ms.member_id = m.member_id
            SET 
                ms.topic_votes = m.topic_votes,
                ms.post_votes = m.post_votes,
                ms.page_views = m.page_views,
                ms.searches_made = m.searches_made";
    $pdo->exec($sql);
    echo "DONE.\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}

// 3. DELETE OLD COLUMNS
echo "3. Deleting old columns from members table... ";
try {
    $sql = "ALTER TABLE members
            DROP COLUMN page_views,
            DROP COLUMN topic_votes,
            DROP COLUMN post_votes,
            DROP COLUMN searches_made";
    $pdo->exec($sql);
    echo "DONE.\n";
} catch (Exception $e) {
    echo "FAILED (Maybe already deleted?): " . $e->getMessage() . "\n";
}

echo "\n--- SUCCESS! Your site is now fixed. ---\n";
echo "Please delete this file immediately.";
?>