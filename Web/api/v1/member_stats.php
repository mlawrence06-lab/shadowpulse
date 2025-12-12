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

    // 0. Optimization: Use provided member_id if valid
    $inputMemberId = isset($input['member_id']) ? (int) $input['member_id'] : 0;

    if ($inputMemberId > 0) {
        $memberId = $inputMemberId;
    } else {
        // 1. Resolve UUID to ID (Fallback)
        $stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_uuid = ?");
        $stmt->execute([$memberUuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            // Return zeros if member not found
            echo json_encode([
                'ok' => true,
                'stats' => [
                    'searches_made' => 0,
                    'page_views' => 0,
                    'topic_votes' => 0,
                    'post_votes' => 0
                ]
            ]);
            exit;
        }
        $memberId = (int) $row['member_id'];
    }

    // 2. Initialize Stats
    $stats = [
        'page_views' => 0,
        'searches_made' => 0,
        'topic_votes' => 0,
        'post_votes' => 0,
        // Ranks can be added later if performance allows
        'page_views_rank' => null,
        'searches_made_rank' => null,
        'topic_votes_rank' => null,
        'post_votes_rank' => null
    ];

    // 3. Fetch from member_stats (Views & Searches) + CALCULATE RANKS
    $stmt = $pdo->prepare("SELECT page_views, searches_made FROM member_stats WHERE member_id = ?");
    $stmt->execute([$memberId]);
    if ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['page_views'] = (int) $r['page_views'];
        $stats['searches_made'] = (int) $r['searches_made'];

        // Rank Views (Higher is better)
        if ($stats['page_views'] > 0) {
            $sqlRank = "SELECT COUNT(*) FROM member_stats WHERE page_views > ?";
            $stmR = $pdo->prepare($sqlRank);
            $stmR->execute([$stats['page_views']]);
            $stats['page_views_rank'] = (int) $stmR->fetchColumn() + 1;
        }

        // Rank Searches (Higher is better)
        if ($stats['searches_made'] > 0) {
            $sqlRank = "SELECT COUNT(*) FROM member_stats WHERE searches_made > ?";
            $stmR = $pdo->prepare($sqlRank);
            $stmR->execute([$stats['searches_made']]);
            $stats['searches_made_rank'] = (int) $stmR->fetchColumn() + 1;
        }
    }

    // 4. Fetch from votes (Live Counts)
    $stmt = $pdo->prepare("SELECT vote_category, COUNT(*) as cnt FROM votes WHERE member_id = ? GROUP BY vote_category");
    $stmt->execute([$memberId]);
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cat = strtolower($r['vote_category']);
        $count = (int) $r['cnt'];

        if ($cat === 'topic') {
            $stats['topic_votes'] = $count;
            // Rank Topics
            if ($count > 0) {
                // Count users who have MORE votes in this category
                $sql = "SELECT COUNT(*) FROM (SELECT member_id, COUNT(*) as c FROM votes WHERE vote_category = 'topic' GROUP BY member_id HAVING c > ?) as sub";
                $stmR = $pdo->prepare($sql);
                $stmR->execute([$count]);
                $stats['topic_votes_rank'] = (int) $stmR->fetchColumn() + 1;
            }
        } else if ($cat === 'post') {
            $stats['post_votes'] = $count;
            // Rank Posts
            if ($count > 0) {
                $sql = "SELECT COUNT(*) FROM (SELECT member_id, COUNT(*) as c FROM votes WHERE vote_category = 'post' GROUP BY member_id HAVING c > ?) as sub";
                $stmR = $pdo->prepare($sql);
                $stmR->execute([$count]);
                $stats['post_votes_rank'] = (int) $stmR->fetchColumn() + 1;
            }
        }
    }

    echo json_encode(['ok' => true, 'stats' => $stats]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
