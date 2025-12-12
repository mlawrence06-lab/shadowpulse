<?php
// update_member_stats.php
// Update stats (searches, page views) for a member.

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

    // 1. Optimization: Use provided member_id
    $memberId = isset($input['member_id']) ? (int) $input['member_id'] : 0;

    if ($memberId <= 0) {
        // Fallback: Resolve UUID
        $stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_uuid = ?");
        $stmt->execute([$memberUuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(['ok' => true]);
            exit;
        }
        $memberId = (int) $row['member_id'];
    }

    // 2. Determine what to update
    $incViews = isset($input['page_views']) ? (int) $input['page_views'] : 0;
    $incSearch = isset($input['searches_made']) ? (int) $input['searches_made'] : 0;

    if ($incViews > 0 || $incSearch > 0) {
        // 3. Upsert into member_stats
        // We use INSERT IGNORE / ON DUPLICATE KEY UPDATE to handle first-time records
        $sql = "INSERT INTO member_stats (member_id, page_views, searches_made, updated_at) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    page_views = page_views + VALUES(page_views),
                    searches_made = searches_made + VALUES(searches_made),
                    updated_at = NOW()";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$memberId, $incViews, $incSearch]);
    }

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
