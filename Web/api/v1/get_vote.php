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

    $voteCount = (int) ($stats['total_count'] ?? 0);
    $rawAvg = (float) ($stats['avg_val'] ?? 0);
    $currentUserVote = isset($stats['current_effective_value']) ? (int) $stats['current_effective_value'] : null;
    $currentDesired = isset($stats['current_desired_value']) ? (int) $stats['current_desired_value'] : null;

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
