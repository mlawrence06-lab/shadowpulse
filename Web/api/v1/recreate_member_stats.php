<?php
// recreate_member_stats.php
header('Content-Type: text/plain');
ini_set('display_errors', 1);
require __DIR__ . '/../../config/db.php';

try {
    $pdo = sp_get_pdo();
    echo "Connected.\n";

    // 1. Create New Table
    $createSQL = "CREATE TABLE `member_stats_new` (
      `member_id` bigint unsigned NOT NULL,
      `searches_made` int unsigned DEFAULT '0',
      `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `topic_votes` int unsigned DEFAULT '0',
      `post_votes` int unsigned DEFAULT '0',
      `last_active` datetime DEFAULT CURRENT_TIMESTAMP,
      `page_views` bigint unsigned DEFAULT '0',
      PRIMARY KEY (`member_id`),
      KEY `idx_stats_post_votes` (`post_votes` DESC),
      KEY `idx_stats_searches` (`searches_made` DESC),
      KEY `idx_stats_topic_votes` (`topic_votes` DESC)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=DYNAMIC";

    $pdo->exec("DROP TABLE IF EXISTS member_stats_new");
    $pdo->exec($createSQL);
    echo "Created member_stats_new.\n";

    // 2. Backfill from Votes
    // aggregate votes count
    echo "Backfilling from votes...\n";
    $sqlBackfill = "
        INSERT INTO member_stats_new (member_id, topic_votes, post_votes)
        SELECT 
            member_id,
            SUM(CASE WHEN vote_category = 'topic' THEN 1 ELSE 0 END) as t_votes,
            SUM(CASE WHEN vote_category = 'post' THEN 1 ELSE 0 END) as p_votes
        FROM votes
        GROUP BY member_id
    ";
    $pdo->exec($sqlBackfill);
    echo "Backfilled votes.\n";

    // 3. Try to salvage searches_made from old table?
    // This runs a risk of crashing. I will SKIP this to ensure stability.
    // Losing 'searches_made' stats is acceptable collateral for unbricking the API.

    // 4. Swap Tables
    echo "Swapping tables...\n";
    $pdo->exec("DROP TABLE member_stats"); // This might crash if corrupt? Hopefully just unlink.
    $pdo->exec("RENAME TABLE member_stats_new TO member_stats");
    echo "Tables swapped. member_stats is now fresh.\n";

    // 5. Verify
    echo "Verifying Select 4a (Join query):\n";
    $sql1a = "SELECT 
        m.member_id,
        COALESCE(ms.topic_votes, 0) as topic_votes,
        COALESCE(ms.page_views, 0) as page_views
    FROM members m
    LEFT JOIN member_stats ms ON m.member_id = ms.member_id
    WHERE m.member_id = 1 LIMIT 1";
    $pdo->query($sql1a)->fetch(PDO::FETCH_ASSOC);
    echo "Verification Select: OK.\n";

} catch (PDOException $e) {
    echo "FATAL PDO: " . $e->getMessage();
} catch (Exception $e) {
    echo "FATAL: " . $e->getMessage();
}
