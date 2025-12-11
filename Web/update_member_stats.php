<?php
/**
 * ShadowPulse - update_member_stats.php
 * Upserts page views and search counts.
 */
ini_set('display_errors', 0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

try {
    if (!file_exists('db.php')) throw new Exception("db.php not found");
    require_once 'db.php';
    $pdo = sp_get_pdo();

    $input = json_decode(file_get_contents('php://input'), true);
    $uuid  = $input['member_uuid'] ?? null;
    
    // Check what we are updating
    $addViews    = isset($input['page_views']) ? (int)$input['page_views'] : 0;
    $addSearches = isset($input['searches_made']) ? (int)$input['searches_made'] : 0;

    if (!$uuid) throw new Exception('Missing UUID');

    // Resolve ID
    $stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_uuid = ?");
    $stmt->execute([$uuid]);
    $id = $stmt->fetchColumn();

    if ($id) {
        // Upsert Stat Row
        $sql = "INSERT INTO member_stats (member_id, page_views, searches_made) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                page_views = page_views + VALUES(page_views),
                searches_made = searches_made + VALUES(searches_made)";
        
        $stmtUpdate = $pdo->prepare($sql);
        $stmtUpdate->execute([$id, $addViews, $addSearches]);
    }

    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>