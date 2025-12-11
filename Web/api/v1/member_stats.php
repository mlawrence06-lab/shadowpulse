<?php
// member_stats.php
// Fetch stats for a specific member.

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$memberUuid = $input['member_uuid'] ?? '';

if (!$memberUuid) {
    echo json_encode(['ok' => false, 'error' => 'Missing UUID']);
    exit;
}

try {
    $pdo = sp_get_pdo();

    // Check if table exists (it might not yet)
    // For now, return basic stub or query if you have the schema
    // Assuming 'member_stats' table or similar logic

    // Simplified: Just return success with 0s if no complex logic yet
    // Real implementation should query DB

    echo json_encode([
        'ok' => true,
        'stats' => [
            'searches' => 0,
            'page_views' => 0
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
