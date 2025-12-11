<?php
// vote.php
// ShadowPulse voting endpoint.
// Expected JSON POST:
// {
//   "member_uuid": "uuid-string",
//   "vote_category": "topic" | "post" | "profile" | "board",
//   "target_id": 123456,
//   "desired_value": 1..5
// }
//
// Response:
// {
//   "ok": true,
//   "effective_value": 1..5,
//   "desired_value": 1..5,
//   "vote_category": "topic" | "post" | ...
// }

declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$memberUuid = isset($data['member_uuid']) ? trim((string) $data['member_uuid']) : '';
$voteCategory = isset($data['vote_category']) ? trim((string) $data['vote_category']) : '';
$targetId = isset($data['target_id']) ? (int) $data['target_id'] : 0;
$desiredValue = isset($data['desired_value']) ? (int) $data['desired_value'] : 0;

if ($memberUuid === '' || $targetId <= 0) { // Validate input ranges
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid fields']);
    exit;
}

if ($desiredValue < 1 || $desiredValue > 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid value']);
    exit;
}

if ($voteCategory === 'post') {
    echo json_encode(['error' => 'Post voting is disabled']);
    exit;
}

require __DIR__ . '/../../config/db.php';

try {
    $pdo = sp_get_pdo();

    // --- NEW: Context Enrichment ---
    require_once __DIR__ . '/../core/metadata.php';

    $contextTopicId = isset($data['context_topic_id']) ? (int) $data['context_topic_id'] : 0;
    $metaDebug = ensure_content_metadata($voteCategory, $targetId, $pdo, $contextTopicId);
    // -------------------------------

    // Look up member_id from member_uuid.
    $stmt = $pdo->prepare('SELECT member_id FROM members WHERE member_uuid = :uuid LIMIT 1');
    $stmt->execute([':uuid' => $memberUuid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'member not found']);
        exit;
    }

    $memberId = (int) $row['member_id'];

    // Call stored procedure: shadowpulse_cast_vote
    // IN  p_member_id       BIGINT UNSIGNED,
    // IN  p_vote_category   VARCHAR(16),
    // IN  p_target_id       BIGINT UNSIGNED,
    // IN  p_desired_value   TINYINT UNSIGNED,
    // OUT p_effective_value TINYINT UNSIGNED
    $spSql = 'CALL shadowpulse_cast_vote(:member_id, :vote_category, :target_id, :desired_value, @p_effective_value)';
    $spStmt = $pdo->prepare($spSql);
    $spStmt->execute([
        ':member_id' => $memberId,
        ':vote_category' => $voteCategory,
        ':target_id' => $targetId,
        ':desired_value' => $desiredValue,
    ]);

    // Read OUT param
    $outRow = $pdo->query('SELECT @p_effective_value AS effective_value')->fetch(PDO::FETCH_ASSOC);
    if (!$outRow || $outRow['effective_value'] === null) {
        throw new RuntimeException('shadowpulse_cast_vote did not return an effective_value');
    }

    $effectiveValue = (int) $outRow['effective_value'];

    echo json_encode([
        'ok' => true,
        'effective_value' => $effectiveValue,
        'desired_value' => $desiredValue,
        'vote_category' => $voteCategory,
        'target_id' => $targetId,
        'debug_metadata' => $metaDebug // Return debug stats to client
    ]);
} catch (Throwable $e) {
    error_log('vote error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
    ]);
}
