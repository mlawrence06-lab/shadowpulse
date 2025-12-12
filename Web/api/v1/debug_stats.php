<?php
// debug_stats.php
// Public diagnostic script to check DB state for stats

header("Content-Type: text/plain");
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/db.php';

try {
    echo "=== DEBUG STATS SCRIPT v3 (RANK TEST) ===\n";
    $pdo = sp_get_pdo();
    echo "=== DATABASE CONNECTION OK ===\n\n";

    // 1. Check Tables
    echo "1. CHECKING TABLES:\n";
    $tables = ['members', 'member_stats', 'votes', 'content_metadata'];
    foreach ($tables as $t) {
        try {
            $pdo->query("SELECT 1 FROM $t LIMIT 1");
            echo "   [OK] Table '$t' exists.\n";
        } catch (Exception $e) {
            echo "   [FAIL] Table '$t' MISSING or Error: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";

    // 2. Look for Members
    $uuid = $_GET['uuid'] ?? 'caa3fc6c-c9f3-46db-8a3f-6ef188165377'; // Default to User's UUID
    echo "2. MEMBER LOOKUP:\n";
    echo "   Target UUID: $uuid\n";

    // 3. Drill Down into Specific Member
    echo "\n3. STATS FOR UUID: $uuid\n";
    $stmt = $pdo->prepare("SELECT * FROM members WHERE member_uuid = ?");
    $stmt->execute([$uuid]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        echo "   [ERROR] Member not found! (Is the UUID correct?)\n";
    } else {
        $mid = $member['member_id'];
        echo "   -> Resolved Member ID: $mid\n";

        // WRITE TEST
        echo "   -> PERFORMING WRITE TEST (Incrementing page_views)...\n";
        try {
            $sql = "INSERT INTO member_stats (member_id, page_views, searches_made, updated_at) 
                    VALUES (?, 1, 0, NOW())
                    ON DUPLICATE KEY UPDATE 
                        page_views = page_views + 1,
                        updated_at = NOW()";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$mid]);
            echo "      [SUCCESS] Write executed.\n";
        } catch (Exception $w) {
            echo "      [FAIL] Write failed: " . $w->getMessage() . "\n";
        }

        // Check member_stats AGAIN + RANK TEST
        $stmt = $pdo->prepare("SELECT * FROM member_stats WHERE member_id = ?");
        $stmt->execute([$mid]);
        $mstats = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   -> Table 'member_stats': ";
        if ($mstats) {
            echo "FOUND. Views=" . $mstats['page_views'] . "\n";

            // TEST RANK QUERY
            $views = $mstats['page_views'];
            $sqlRank = "SELECT COUNT(*) FROM member_stats WHERE page_views > ?";
            $stmR = $pdo->prepare($sqlRank);
            $stmR->execute([$views]);
            $rank = (int) $stmR->fetchColumn() + 1;
            echo "      [TEST] Calculated Rank: $rank (If this shows a number, the DB works)\n";

        } else {
            echo "NOT FOUND (Row missing).\n";
        }

        // Check votes
        $stmt = $pdo->prepare("SELECT vote_category, COUNT(*) as cnt FROM votes WHERE member_id = ? GROUP BY vote_category");
        $stmt->execute([$mid]);
        $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   -> Table 'votes':\n";
        if ($votes) {
            foreach ($votes as $v) {
                echo "      Category '" . $v['vote_category'] . "': " . $v['cnt'] . "\n";
            }
        } else {
            echo "      NO VOTES FOUND.\n";
        }
    }

} catch (Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage();
}
