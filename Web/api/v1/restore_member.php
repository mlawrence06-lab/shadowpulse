<?php
/**
 * ShadowPulse - restore_member.php
 * Simplified Logic: 
 * The 'restore_code' provided by the user is treated directly as the 'member_uuid'.
 */

ini_set('display_errors', 0);
require_once __DIR__ . '/cors.php';

try {
    if (!file_exists(__DIR__ . '/../../config/db.php')) {
        throw new Exception("db.php not found.");
    }
    require_once __DIR__ . '/../../config/db.php';
    $pdo = sp_get_pdo();

    $input = json_decode(file_get_contents('php://input'), true);
    $code = trim($input['restore_code'] ?? '');

    if (!$code) {
        throw new Exception('Missing code');
    }

    // Direct Lookup: Treat the input code as the member_uuid
    $stmt = $pdo->prepare("SELECT member_id, member_uuid, restore_ack, pref_theme, pref_search, pref_btc_source FROM members WHERE member_uuid = ?");
    $stmt->execute([$code]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($member) {
        // Success! Return Identity + Preferences
        echo json_encode([
            'member_id' => (int) $member['member_id'],
            'member_uuid' => $member['member_uuid'],
            'restore_ack' => (bool) $member['restore_ack'],
            'prefs' => [
                'theme' => $member['pref_theme'] ?? 'light',
                'search' => $member['pref_search'] ?? 'bitlist',
                'btc_source' => $member['pref_btc_source'] ?? 'binance'
            ]
        ]);
    } else {
        // UUID not found
        http_response_code(404);
        echo json_encode(['error' => 'Invalid code']);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>