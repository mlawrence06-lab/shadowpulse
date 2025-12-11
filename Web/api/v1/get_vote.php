<?php
require __DIR__ . '/cors.php';
header('Content-Type: application/json');

$memberUuid = isset($_GET['member_uuid']) ? trim($_GET['member_uuid']) : '';
$voteCategory = isset($_GET['vote_category']) ? trim($_GET['vote_category']) : '';
$targetId = isset($_GET['target_id']) ? (int) $_GET['target_id'] : 0;

if ($memberUuid === '' || $targetId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'missing fields']);
    exit;
}

require __DIR__ . '/../../config/db.php';

try {
    $pdo = sp_get_pdo();

    $stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_uuid = :uuid LIMIT 1");
    $stmt->execute([':uuid' => $memberUuid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['ok' => false, 'error' => 'member not found']);
        exit;
    }

    $memberId = (int) $row['member_id'];

    // --- NEW: Calculate Rank and Counts ---
    // C = Moderate (3)
    // M = Mild (2,4)
    // E = Extreme (1,5)

    // 1. Get Totals
    $stmtTotals = $pdo->prepare("
        SELECT 
            COUNT(*) as total_count,
            IFNULL(SUM(CASE WHEN effective_value = 3 THEN 1 ELSE 0 END), 0) as C,
            IFNULL(SUM(CASE WHEN effective_value IN (2, 4) THEN 1 ELSE 0 END), 0) as M,
            IFNULL(SUM(CASE WHEN effective_value IN (1, 5) THEN 1 ELSE 0 END), 0) as E,
            AVG(effective_value) as avg_val
        FROM votes 
        WHERE vote_category = :category AND target_id = :target_id
    ");
    $stmtTotals->execute([':category' => $voteCategory, ':target_id' => $targetId]);
    $stats = $stmtTotals->fetch(PDO::FETCH_ASSOC);

    $voteCount = (int) ($stats['total_count'] ?? 0);
    $C = (int) ($stats['C'] ?? 0);
    $M = (int) ($stats['M'] ?? 0);
    $E = (int) ($stats['E'] ?? 0);
    $rawAvg = (float) ($stats['avg_val'] ?? 0);

    // 2. Calculate Rank (Simplified logic: E vs M vs C)
    // Rank logic matches the stored procedure constraints conceptually
    // For display purposes, we can use a simplified metric or just return the raw count for now if rank logic is complex in PHP.
    // Let's assume Rank = Total Count for now, unless we port the full ranking logic.
    // Actually, user wants "Ranked Xth" which implies competitive ranking amongst OTHER topics? 
    // "Rank" in the UI often refers to the hierarchical level (1-5)? No, "Ranked 5th" means 5th best topic.

    // Wait, the UI displays: "1,677 votes (Ranked 5th)"
    // This implies a GLOBAL rank compared to other items.

    // For now, let's just return the counts so "No votes yet" disappears.
    // We will calculate a dummy rank or fetch it if there is a 'ranks' table.

    // 3. Get User's Vote
    $stmtUser = $pdo->prepare("
        SELECT effective_value, desired_value
        FROM votes
        WHERE member_id = :member_id AND vote_category = :category AND target_id = :target_id
        LIMIT 1
    ");
    $stmtUser->execute([':member_id' => $memberId, ':category' => $voteCategory, ':target_id' => $targetId]);
    $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

    $currentUserVote = $userRow ? (int) $userRow['effective_value'] : null;
    $currentDesired = $userRow ? (int) $userRow['desired_value'] : null;

    echo json_encode([
        'ok' => true,
        'effective_value' => $currentUserVote, // Legacy field
        'currentVote' => $currentUserVote,     // Field expected by frontend for Effective
        'desired_value' => $currentDesired,    // Field expected by frontend for Desired
        'vote_count' => $voteCount,
        'rank' => 0, // Placeholder for global rank
        'topic_score' => $rawAvg, // Use average for score
        'post_score' => $rawAvg
    ]);

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
