<?php
require __DIR__ . '/cors.php';

// update_restore_ack.php
// Update the restore_ack flag for a member.
// Expected JSON POST: { "member_uuid": "uuid-string", "restore_ack": true/false }

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

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['member_uuid']) || !array_key_exists('restore_ack', $input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON or missing fields']);
    exit;
}

$memberUuid = trim((string) $input['member_uuid']);
$restoreAck = (bool) $input['restore_ack'];

if ($memberUuid === '' || strlen($memberUuid) > 64) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid member_uuid']);
    exit;
}

require __DIR__ . '/../../config/db.php';

try {
    $pdo = sp_get_pdo();

    $stmt = $pdo->prepare('UPDATE members SET restore_ack = :ack WHERE member_uuid = :uuid');
    $stmt->execute([
        ':ack' => $restoreAck ? 1 : 0,
        ':uuid' => $memberUuid,
    ]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
