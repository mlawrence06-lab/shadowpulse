<?php
/**
 * ShadowPulse - member_stats.php
 * Now calculates and returns Global Ranks for all stats.
 */

// Enable error reporting for debugging (but keep display_errors off to protect JSON)
ini_set('display_errors', 0); 
error_reporting(E_ALL);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // 1. Include YOUR existing DB file
    if (!file_exists('db.php')) {
        throw new Exception("db.php file not found on server.");
    }
    require_once 'db.php'; 

    // 2. Get the connection
    $pdo = sp_get_pdo();

    // 3. Get Input
    $inputRaw = file_get_contents('php://input');
    $input = json_decode($inputRaw, true);
    $uuid  = $input['member_uuid'] ?? null;

    if (!$uuid) {
        throw new Exception('Missing member_uuid in request.');
    }

    // 4. Resolve Member ID
    $stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_uuid = ?");
    $stmt->execute([$uuid]);
    $memberId = $stmt->fetchColumn();

    if (!$memberId) {
        // Unknown member -> Return zeros
        echo json_encode([
            'page_views' => 0, 'searches_made' => 0,
            'topic_votes' => 0, 'post_votes' => 0,
            'page_views_rank' => null, 'topic_votes_rank' => null,
            'post_votes_rank' => null, 'searches_made_rank' => null
        ]);
        exit;
    }

    // 5. Get VALUES (Counts)
    
    // Active Topic Votes
    $stmtTopic = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE member_id = ? AND vote_category = 'topic'");
    $stmtTopic->execute([$memberId]);
    $topicVotes = (int)$stmtTopic->fetchColumn();

    // Active Post Votes
    $stmtPost = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE member_id = ? AND vote_category = 'post'");
    $stmtPost->execute([$memberId]);
    $postVotes = (int)$stmtPost->fetchColumn();

    // Static Stats (Views/Searches)
    $stmtStats = $pdo->prepare("SELECT page_views, searches_made FROM member_stats WHERE member_id = ?");
    $stmtStats->execute([$memberId]);
    $staticStats = $stmtStats->fetch(PDO::FETCH_ASSOC);

    $pageViews = $staticStats ? (int)$staticStats['page_views'] : 0;
    $searches  = $staticStats ? (int)$staticStats['searches_made'] : 0;

    // 6. Calculate RANKS
    // Rank = (Number of people with MORE than you) + 1
    
    // Helper: Rank for Votes (Complex Grouping)
    function getVoteRank($pdo, $category, $myCount) {
        if ($myCount === 0) return null; // Don't rank 0
        $sql = "SELECT COUNT(*) FROM (
                    SELECT member_id FROM votes 
                    WHERE vote_category = ? 
                    GROUP BY member_id 
                    HAVING COUNT(*) > ?
                ) as higher";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$category, $myCount]);
        return (int)$stmt->fetchColumn() + 1;
    }

    // Helper: Rank for Simple Stats (Simple Compare)
    function getStatRank($pdo, $col, $myCount) {
        if ($myCount === 0) return null; // Don't rank 0
        // Use whitelisting for column name to prevent SQL injection in internal function
        $validCols = ['page_views', 'searches_made'];
        if (!in_array($col, $validCols)) return null;

        $sql = "SELECT COUNT(*) FROM member_stats WHERE $col > ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$myCount]);
        return (int)$stmt->fetchColumn() + 1;
    }

    $topicRank = getVoteRank($pdo, 'topic', $topicVotes);
    $postRank  = getVoteRank($pdo, 'post', $postVotes);
    $viewRank  = getStatRank($pdo, 'page_views', $pageViews);
    $searchRank = getStatRank($pdo, 'searches_made', $searches);

    // 7. Return JSON
    echo json_encode([
        'page_views'    => $pageViews,
        'searches_made' => $searches,
        'topic_votes'   => $topicVotes, 
        'post_votes'    => $postVotes,
        
        'page_views_rank'    => $viewRank,
        'topic_votes_rank'   => $topicRank,
        'post_votes_rank'    => $postRank,
        'searches_made_rank' => $searchRank
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
}
?>