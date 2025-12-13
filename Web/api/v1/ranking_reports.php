<?php
// ranking_reports.php
// Unified Ranking Report Endpoint

require_once 'cors.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

try {
    // Initialize PDO
    if (!function_exists('sp_get_pdo')) {
        throw new Exception("Database connection function missing.");
    }
    $pdo = sp_get_pdo();

    // 1. Get Parameters
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'page_views';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
    if ($limit > 100)
        $limit = 100;

    // Validate Sort Column
    $validSorts = ['page_views', 'searches', 'topic_votes', 'post_votes'];
    if (!in_array($sort, $validSorts)) {
        $sort = 'page_views';
    }

    // Map 'searches' param to DB column 'searches_made'
    $orderBy = $sort;
    if ($sort === 'searches') {
        $orderBy = 'searches_made';
    }

    // 2. Query
    // Restore m.custom_name logic
    $sql = "
        SELECT 
            m.member_id,
            m.custom_name,
            COALESCE(ms.page_views, 0) as page_views,
            COALESCE(ms.searches_made, 0) as searches_made,
            COALESCE(ms.topic_votes, 0) as topic_votes,
            COALESCE(ms.post_votes, 0) as post_votes
        FROM members m
        LEFT JOIN member_stats ms ON m.member_id = ms.member_id
        ORDER BY ms.$orderBy DESC
        LIMIT ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $limit, PDO::PARAM_INT);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Format
    $data = [];
    $rank = 1;
    foreach ($results as $row) {
        // Use custom_name if available, otherwise member_id
        $display = !empty($row['custom_name']) ? htmlspecialchars($row['custom_name']) : (int) $row['member_id'];

        $data[] = [
            'Rank' => $rank++,
            'MemberID' => (int) $row['member_id'],
            'Username' => $display,
            'PageViews' => (int) $row['page_views'],
            'Searches' => (int) $row['searches_made'],
            'TopicVotes' => (int) $row['topic_votes'],
            'PostVotes' => (int) $row['post_votes']
        ];
    }

    echo json_encode(['data' => $data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>