<?php
// api/v1/vote_pyramid.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/cors.php';

header('Content-Type: application/json');

try {
    $pdo = sp_get_pdo();

    $uuid = $_GET['uuid'] ?? null;

    if (!$uuid) {
        echo json_encode(['success' => false, 'error' => 'UUID parameter is required for diagnostic report']);
        exit;
    }

    // Get Member ID and Name
    $stmtMember = $pdo->prepare("SELECT member_id, custom_name FROM members WHERE member_uuid = ?");
    $stmtMember->execute([$uuid]);
    $member = $stmtMember->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        echo json_encode(['success' => false, 'error' => 'Member not found']);
        exit;
    }

    $memberId = $member['member_id'];
    $displayName = !empty($member['custom_name']) ? $member['custom_name'] : "Member $memberId";

    // --- TOPIC VOTES ---
    // 1. Topic Pyramid
    $stmtTopicP = $pdo->prepare("
        SELECT effective_value as level, COUNT(*) as count 
        FROM votes 
        WHERE member_id = ? AND vote_category = 'topic'
        GROUP BY effective_value 
        ORDER BY effective_value DESC
    ");
    $stmtTopicP->execute([$memberId]);
    $topicPyramid = $stmtTopicP->fetchAll(PDO::FETCH_ASSOC);

    // 2. Topic Queue
    $stmtTopicQ = $pdo->prepare("
        SELECT id, target_id, desired_value, effective_value, created_at
        FROM votes 
        WHERE member_id = ? AND vote_category = 'topic'
        AND desired_value != effective_value
        ORDER BY created_at ASC LIMIT 500
    ");
    $stmtTopicQ->execute([$memberId]);
    $topicQueue = $stmtTopicQ->fetchAll(PDO::FETCH_ASSOC);


    // --- POST VOTES ---
    // 3. Post Pyramid
    $stmtPostP = $pdo->prepare("
        SELECT effective_value as level, COUNT(*) as count 
        FROM votes 
        WHERE member_id = ? AND vote_category = 'post'
        GROUP BY effective_value 
        ORDER BY effective_value DESC
    ");
    $stmtPostP->execute([$memberId]);
    $postPyramid = $stmtPostP->fetchAll(PDO::FETCH_ASSOC);

    // 4. Post Queue
    $stmtPostQ = $pdo->prepare("
        SELECT id, target_id, desired_value, effective_value, created_at
        FROM votes 
        WHERE member_id = ? AND vote_category = 'post'
        AND desired_value != effective_value
        ORDER BY created_at ASC LIMIT 500
    ");
    $stmtPostQ->execute([$memberId]);
    $postQueue = $stmtPostQ->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode([
        'success' => true,
        'member_name' => $displayName,
        'topic_pyramid' => $topicPyramid,
        'topic_queue' => $topicQueue,
        'post_pyramid' => $postPyramid,
        'post_queue' => $postQueue
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
