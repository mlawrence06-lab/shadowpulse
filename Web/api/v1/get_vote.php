<?php
require __DIR__ . '/cors.php';
header('Content-Type: application/json');

$memberUuid = isset($_GET['member_uuid']) ? trim($_GET['member_uuid']) : '';
$voteCategory = isset($_GET['vote_category']) ? trim($_GET['vote_category']) : '';
$targetId = isset($_GET['target_id']) ? (int) $_GET['target_id'] : 0;

if ($memberUuid === '' || $targetId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'missing fields']);
    exit;
}

require __DIR__ . '/../../config/db.php';

try {
    $pdo = sp_get_pdo();

    $stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_uuid = :uuid LIMIT 1");
    $stmt->execute([':uuid' => $memberUuid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['ok' => false, 'error' => 'member not found']);
        exit;
    }

    $memberId = (int) $row['member_id'];

    $stmt = $pdo->prepare("
        SELECT effective_value
        FROM votes
        WHERE member_id     = :member_id
          AND vote_category = :category
          AND target_id     = :target_id
        LIMIT 1
    ");
    $stmt->execute([
        ':member_id' => $memberId,
        ':category' => $voteCategory,
        ':target_id' => $targetId
    ]);

    $row2 = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row2) {
        echo json_encode(['ok' => true, 'effective_value' => null]);
        exit;
    }

    echo json_encode(['ok' => true, 'effective_value' => (int) $row2['effective_value']]);

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
