<?php
require __DIR__ . '/cors.php';
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Start Session for Caching (Reduces DB Load)
session_start();

$memberUuid = isset($_GET['member_uuid']) ? trim($_GET['member_uuid']) : '';
$voteCategory = isset($_GET['vote_category']) ? trim($_GET['vote_category']) : '';
$targetId = isset($_GET['target_id']) ? (int) $_GET['target_id'] : 0;

if ($memberUuid === '' || $targetId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'missing fields']);
    exit;
}

// Session Cache Key
$cacheKey = "sp_vote_{$memberUuid}_{$voteCategory}_{$targetId}";

// 1. Check Session Cache (Server-side "local storage")
if (isset($_SESSION[$cacheKey])) {
    $cached = $_SESSION[$cacheKey];
    // Optional: Check timestamp if we store it, for now rely on session lifetime
    if (isset($cached['timestamp']) && (time() - $cached['timestamp'] < 30)) { // 30s TTL
        echo json_encode($cached['payload']);
        exit;
    }
}

require __DIR__ . '/../../config/db.php';

try {
    $pdo = sp_get_pdo();

    // Call stored procedure: shadowpulse_get_vote_summary
    // IN  p_member_uuid, p_vote_category, p_target_id
    // RETURNS result set with total_count, avg_val, current_effective_value, current_desired_value

    // Call stored procedure: shadowpulse_get_vote_summary
    // IN  p_member_uuid, p_vote_category, p_target_id
    // RETURNS result set with total_count, avg_val, current_effective_value, current_desired_value

    $spSql = 'CALL shadowpulse_get_vote_summary(:uuid, :category, :target_id)';
    $stmt = $pdo->prepare($spSql);
    $stmt->execute([
        ':uuid' => $memberUuid,
        ':category' => $voteCategory,
        ':target_id' => $targetId
    ]);

    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stats) {
        // Fallback if SP returns nothing (should not happen with aggregates)
        $stats = [
            'total_count' => 0,
            'avg_val' => 0,
            'current_effective_value' => null,
            'current_desired_value' => null
        ];
    }

    // Fix for 2014 Error: Flush any remaining result sets from the stored procedure
    // before running the next SELECT query (target label lookup).
    do {
        try {
            $stmt->fetchAll();
        } catch (Exception $e) {
        }
    } while ($stmt->nextRowset());
    $stmt = null;

    $voteCount = (int) ($stats['total_count'] ?? 0);
    $rawAvg = (float) ($stats['avg_val'] ?? 0);
    $currentUserVote = isset($stats['current_effective_value']) ? (int) $stats['current_effective_value'] : null;
    $currentDesired = isset($stats['current_desired_value']) ? (int) $stats['current_desired_value'] : null;

    // --- FETCH TARGET LABEL ---
    $targetLabel = null;
    if ($voteCategory === 'topic') {
        $stmtLabel = $pdo->prepare("SELECT topic_title FROM topics_info WHERE topic_id = ?");
        $stmtLabel->execute([$targetId]);
        if ($rowLabel = $stmtLabel->fetch(PDO::FETCH_ASSOC)) {
            $targetLabel = $rowLabel['topic_title'];
        }
        $stmtLabel->closeCursor();
    } elseif ($voteCategory === 'post') {
        // Look up Topic ID from content_metadata, then Title from topics_info
        $stmtLabel = $pdo->prepare("
            SELECT t.topic_title 
            FROM content_metadata c
            JOIN topics_info t ON c.topic_id = t.topic_id
            WHERE c.post_id = ?
        ");
        $stmtLabel->execute([$targetId]);
        if ($rowLabel = $stmtLabel->fetch(PDO::FETCH_ASSOC)) {
            $targetLabel = $rowLabel['topic_title'];
        }
        $stmtLabel->closeCursor();
    } elseif ($voteCategory === 'profile') {
        // Target ID is the Member ID
        // First check 'members' table (registered users)
        // If not found, check 'content_metadata' for author_name? 
        // User requested: "display [Name] or [Profile 30747]"

        // Check `members` table (if they use the extension)
        // Note: member_id in members table corresponds to the EXTENSION USER ID, which maps to SMF ID.
        // Wait, member_id in `members` IS the SMF ID? No, it's auto-increment. 
        // We probably don't store the SMF ID in strict relation unless we scrape it.
        // ACTUALLY, checking metadata.php, we store 'author_id' (SMF ID) in content_metadata.
        // So for 'profile' votes, target_id IS the SMF ID (author_id).

        // Check content_metadata for ANY post by this author to get the name
        $stmtLabel = $pdo->prepare("SELECT author_name FROM content_metadata WHERE author_id = ? LIMIT 1");
        $stmtLabel->execute([$targetId]);
        if ($rowLabel = $stmtLabel->fetch(PDO::FETCH_ASSOC)) {
            $targetLabel = $rowLabel['author_name'];
        } else {
            // Fallback: Check if they are a registered extension user (unlikely to match SMF ID 1:1 unless synchronized?)
            // Actually, `members` table doesn't have an SMF ID column shown in DESCcribe. 
            // So we rely on content_metadata.
        }
        $stmtLabel->closeCursor();

        // If still null, frontend handles fallback.
    } elseif ($voteCategory === 'board') {
        // Boards: Frontend has the list. Backend sends null.
    }
    // --------------------------

    $response = [
        'ok' => true,
        'effective_value' => $currentUserVote, // Legacy field
        'currentVote' => $currentUserVote,     // Field expected by frontend for Effective
        'desired_value' => $currentDesired,    // Field expected by frontend for Desired
        'vote_count' => $voteCount,
        'rank' => 0, // Placeholder for global rank
        'topic_score' => $rawAvg, // Use average for score
        'post_score' => $rawAvg,
        'target_label' => $targetLabel // <-- Added
    ];

    // 2. Save to Session Cache
    $_SESSION[$cacheKey] = [
        'timestamp' => time(),
        'payload' => $response
    ];

    // Debug Logging
    $logMsg = date('[Y-m-d H:i:s] ') . "REQ: uuid=$memberUuid cat=$voteCategory id=$targetId | RES: count=$voteCount score=$rawAvg label=$targetLabel\n";
    file_put_contents(__DIR__ . '/vote_debug.log', $logMsg, FILE_APPEND);

    echo json_encode($response);

    // --- PIGGYBACK: Process Ninja Queue in Background ---
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request(); // Flush connection to client so they don't wait
    }

    // Attempt to run queue if time permits
    try {
        require_once __DIR__ . '/../../core/queue_runner.php';
        run_ninja_queue($pdo);
    } catch (Exception $e) {
        // Silently fail logging?
    }

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
?>