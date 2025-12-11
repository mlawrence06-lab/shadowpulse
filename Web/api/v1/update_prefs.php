<?php
/**
 * ShadowPulse - update_prefs.php
 * Saves user preferences (Theme, Search, BTC Source).
 */
ini_set('display_errors', 0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

try {
    require_once 'db.php';
    $pdo = sp_get_pdo();

    $input = json_decode(file_get_contents('php://input'), true);
    $uuid = $input['member_uuid'] ?? null;
    
    if (!$uuid) throw new Exception('Missing UUID');

    // Prepare fields to update
    $fields = [];
    $params = [];

    if (isset($input['pref_theme'])) {
        $fields[] = "pref_theme = ?";
        $params[] = $input['pref_theme'];
    }
    if (isset($input['pref_search'])) {
        $fields[] = "pref_search = ?";
        $params[] = $input['pref_search'];
    }
    if (isset($input['pref_btc_source'])) {
        $fields[] = "pref_btc_source = ?";
        $params[] = $input['pref_btc_source'];
    }

    if (empty($fields)) {
        echo json_encode(['ok' => true, 'message' => 'No changes']);
        exit;
    }

    // Add UUID for WHERE clause
    $params[] = $uuid;

    $sql = "UPDATE members SET " . implode(', ', $fields) . " WHERE member_uuid = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>