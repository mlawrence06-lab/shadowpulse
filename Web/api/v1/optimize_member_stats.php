<?php
// optimize_member_stats.php
header('Content-Type: text/plain');
ini_set('display_errors', 1);
require __DIR__ . '/../../config/db.php';

try {
    $pdo = sp_get_pdo();
    echo "Connected.\n";

    echo "Optimizing member_stats...\n";
    $pdo->query("OPTIMIZE TABLE member_stats")->fetchAll();
    echo "Optimization triggered (Note: InnoDB supports recreate).\n";

    echo "Analyzing member_stats...\n";
    $pdo->query("ANALYZE TABLE member_stats")->fetchAll();
    echo "Analyze complete.\n";

    echo "Verifying Select 4a (Join without page_views) again:\n";
    $sql1a = "SELECT 
        m.member_id,
        COALESCE(ms.topic_votes, 0) as topic_votes
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
