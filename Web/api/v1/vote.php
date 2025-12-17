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

require __DIR__ . '/cors.php';
header('Content-Type: application/json');

// Session Start for cache invalidation
session_start();

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
$targetId = isset($_GET['target_id']) ? (int) $_GET['target_id'] : (isset($data['target_id']) ? (int) $data['target_id'] : 0);
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

require __DIR__ . '/../../config/db.php';

try {
    $pdo = sp_get_pdo();

    // (Metadata population moved to Async block at end)


    // Look up member_id from member_uuid.
    $stmt = $pdo->prepare('SELECT member_id FROM members WHERE member_uuid = :uuid LIMIT 1');
    $row = false; // Initialize to avoid undefined variable if try block fails
    try {
        $stmt->execute([':uuid' => $memberUuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
    } catch (\Throwable $e) {
        throw new Exception("[Member Lookup Error: " . $e->getMessage() . "]");
    }

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'member not found']);
        exit;
    }

    $memberId = (int) $row['member_id'];

    // Call stored procedure: shadowpulse_cast_vote
    // IN  p_member_id       BIGINT UNSIGNED,
    // IN  p_target_id       BIGINT UNSIGNED,
    // IN  p_desired_value   TINYINT UNSIGNED,
    // OUT p_effective_value TINYINT UNSIGNED
    // Call stored procedure via OUT params
    // CALL shadowpulse_cast_vote(...,    // Call stored procedure via OUT params
    // 1. EXECUTE SP (Updates DB Only)
    // We bind a dummy for OUT p_effective_value or use MySQL user var

    $spSql = 'CALL shadowpulse_cast_vote(:member_id, :vote_category, :target_id, :desired_value, @p_effective_value)';
    $spStmt = $pdo->prepare($spSql);
    try {
        $spStmt->execute([
            ':member_id' => $memberId,
            ':vote_category' => $voteCategory,
            ':target_id' => $targetId,
            ':desired_value' => $desiredValue,
        ]);
        $spStmt = null;
    } catch (\Throwable $e) {
        throw new Exception("[SP Execution Error: " . $e->getMessage() . "]");
    }

    // 2. FETCH UPDATED STATS + USER VOTE (Standard SELECT)
    // Matches User's "One Call from Frontend" requirement (but two SQL queries)

    $effectiveValue = 0;
    $voteCount = 0;
    $totalScore = 0;

    try {
        if ($voteCategory === 'topic') {
            $sql = "SELECT v.effective_value, s.vote_count, s.total_score 
                    FROM votes v 
                    LEFT JOIN stats_topics s ON s.topic_id = v.target_id
                    WHERE v.member_id = :mid AND v.vote_category = 'topic' AND v.target_id = :tid 
                    LIMIT 1";
        } elseif ($voteCategory === 'post') {
            $sql = "SELECT v.effective_value, s.vote_count, s.total_score 
                    FROM votes v 
                    LEFT JOIN stats_posts s ON s.post_id = v.target_id
                    WHERE v.member_id = :mid AND v.vote_category = 'post' AND v.target_id = :tid 
                    LIMIT 1";
        } elseif ($voteCategory === 'profile') {
            $sql = "SELECT v.effective_value, s.vote_count, s.total_score 
                    FROM votes v 
                    LEFT JOIN stats_profiles s ON s.member_id = v.target_id
                    WHERE v.member_id = :mid AND v.vote_category = 'profile' AND v.target_id = :tid 
                    LIMIT 1";
        }

        if (isset($sql)) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':mid' => $memberId, ':tid' => $targetId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $effectiveValue = (int) $row['effective_value'];
                $voteCount = (int) $row['vote_count'];
                $totalScore = (int) $row['total_score'];
            }
        }
    } catch (\Throwable $e) {
        // Fallback or ignore
    }

    if ($effectiveValue === 0) {
        // Fallback to reading OUT variable if SELECT above failed?
        // But SP sets @p_effective_value.
        $res = $pdo->query("SELECT @p_effective_value as ev")->fetch(PDO::FETCH_ASSOC);
        if ($res) {
            $effectiveValue = (int) $res['ev'];
        }
    }

    // --- SESSION CACHE INVALIDATION ---
    // If the user voted, invalid their get_vote cache for this item
    $cacheKey = "sp_vote_{$memberUuid}_{$voteCategory}_{$targetId}";
    if (isset($_SESSION[$cacheKey])) {
        unset($_SESSION[$cacheKey]);
    }
    // ----------------------------------

    echo json_encode([
        'ok' => true,
        'effective_value' => $effectiveValue,
        'desired_value' => $desiredValue,
        'vote_category' => $voteCategory,
        'target_id' => $targetId,
        'vote_count' => $voteCount,
        'total_score' => $totalScore,
        'debug_metadata' => $metaDebug // Return debug stats to client
    ]);

    // --- ASYNC METADATA POPULATION ---
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    $logFile = __DIR__ . '/debug_log.txt';
    $log = function ($msg) use ($logFile) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
    };

    try {
        $log("Async Start. Cat: $voteCategory, ID: $targetId");
        require_once __DIR__ . '/../core/metadata.php';

        $contextTopicId = isset($data['context_topic_id']) ? (int) $data['context_topic_id'] : 0;
        $res = ensure_content_metadata($voteCategory, $targetId, $pdo, $contextTopicId);

        $log("Result: " . json_encode($res));

    } catch (\Throwable $e) {
        $log("Error: " . $e->getMessage());
        error_log("Metadata Async Error: " . $e->getMessage());
    }

} catch (Throwable $e) {
    error_log('vote error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
    ]);
}
?>