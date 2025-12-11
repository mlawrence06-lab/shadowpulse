<?php
/**
 * ShadowPulse - log_search.php
 * Records individual search queries for analytics.
 */

// Quiet errors for JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    if (!file_exists('db.php')) {
        throw new Exception("db.php not found.");
    }
    require_once 'db.php';
    $pdo = sp_get_pdo();

    // Get Input
    $input = json_decode(file_get_contents('php://input'), true);
    $uuid = $input['member_uuid'] ?? null;
    $term = trim($input['search_term'] ?? '');
    $engine = (int)($input['engine_id'] ?? 1);

    if (!$uuid || $term === '') {
        // Silently fail for empty searches or missing users
        echo json_encode(['ok' => false]);
        exit;
    }

    // Resolve Member ID
    $stmtMember = $pdo->prepare("SELECT id FROM members WHERE member_uuid = ?");
    $stmtMember->execute([$uuid]);
    $memberId = $stmtMember->fetchColumn();

    if ($memberId) {
        // Insert Log
        $stmtLog = $pdo->prepare("INSERT INTO search_logs (member_id, search_term, engine_id) VALUES (?, ?, ?)");
        $stmtLog->execute([$memberId, $term, $engine]);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Unknown member']);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>