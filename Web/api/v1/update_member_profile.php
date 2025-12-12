<?php
// update_member_profile.php
// Update member profile settings (custom name).

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$memberUuid = $input['member_uuid'] ?? '';
$customName = isset($input['custom_name']) ? trim($input['custom_name']) : null;

if (!$memberUuid) {
    echo json_encode(['ok' => false, 'error' => 'Missing UUID']);
    exit;
}

// Validation / Sanitization
if ($customName !== null) {
    // Strip tags to prevent XSS
    $customName = strip_tags($customName);
    // Limit length
    if (strlen($customName) > 32) {
        echo json_encode(['ok' => false, 'error' => 'Name too long (max 32 chars).']);
        exit;
    }
    // Allow empty string to reset to default
}

try {
    $pdo = sp_get_pdo();

    // Update custom_name
    // We assume the member exists or we insert? For now, we update based on UUID if exists.
    // If saving profile for the first time, we might need to ensure member record exists.
    // But usually extension creates member record on first ping. 
    // Let's assume member exists.

    $sql = "UPDATE members SET custom_name = ? WHERE member_uuid = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customName, $memberUuid]);

    echo json_encode(['ok' => true, 'sanitized_name' => $customName]);
} catch (Exception $e) {
    if ($e instanceof PDOException && $e->getCode() == 23000) {
        // Integrity constraint violation (Duplicate entry)
        echo json_encode(['ok' => false, 'error' => 'Name is already taken.']);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
}
