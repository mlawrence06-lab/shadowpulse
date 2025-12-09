<?php
require __DIR__ . '/cors.php';

// bootstrap_member.php
// Ensure a members row exists for the given member_uuid and return its info.
// Expected JSON POST: { "member_uuid": "uuid-string" }

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Read JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data) || empty($data['member_uuid'])) {
    http_response_code(400);
    echo json_encode(['error' => 'member_uuid is required']);
    exit;
}

$memberUuid = trim($data['member_uuid']);

// Very loose sanity check; adjust if you want stricter UUID validation
if (!preg_match('/^[0-9a-fA-F-]{8,36}$/', $memberUuid)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid member_uuid']);
    exit;
}

// ---- DB CONNECTION ----
// Uses your existing helper from db.php
require __DIR__ . '/db.php';

try {
    $pdo = sp_get_pdo();
    if (!$pdo instanceof PDO) {
        throw new RuntimeException('sp_get_pdo() did not return a PDO instance');
    }

    // Insert if not exists; new stats columns have defaults so this is safe
    $stmt = $pdo->prepare('INSERT IGNORE INTO members (member_uuid) VALUES (:uuid)');
    $stmt->execute([':uuid' => $memberUuid]);

    // Fetch the row
    $stmt = $pdo->prepare(
        'SELECT member_id, member_uuid, restore_ack, created_at, last_seen_at
         FROM members
         WHERE member_uuid = :uuid
         LIMIT 1'
    );
    $stmt->execute([':uuid' => $memberUuid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to load member']);
        exit;
    }

    echo json_encode([
        'member_id'    => (int)$row['member_id'],
        'member_uuid'  => $row['member_uuid'],
        'restore_ack'  => (bool)$row['restore_ack'],
        'created_at'   => $row['created_at'],
        'last_seen_at' => $row['last_seen_at'],
    ]);
} catch (Throwable $e) {
    // TEMP: expose message while we debug; you can remove "message" later
    error_log('bootstrap_member error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error'   => 'Server error',
        'message' => $e->getMessage(),
        'code'    => $e->getCode(),
    ]);
}
