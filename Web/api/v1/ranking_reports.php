<?php
// ranking_reports.php
// Refactored: Extension Usage Stats (Rank, Member, Views, Total Votes, Searches)
// v1.5: Reverted to source STRICTLY from 'members' table as requested.
// Logic: If 'members' table is empty, report is empty.

require_once 'cors.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");

try {
    if (!function_exists('sp_get_pdo')) {
        throw new Exception("Database connection function missing.");
    }
    $pdo = sp_get_pdo();

    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'rank';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
    if ($limit > 100)
        $limit = 100;

    // Determine Sort Order
    $orderBy = "total_votes DESC"; // Default
    switch ($sort) {
        case 'Member':
            $orderBy = "custom_name ASC";
            break;
        case 'Page Views':
            $orderBy = "page_views DESC";
            break;
        case 'Total Votes':
            $orderBy = "total_votes DESC";
            break;
        case 'Searches':
            $orderBy = "searches DESC";
            break;
        case 'Rank':
        default:
            $orderBy = "total_votes DESC";
            break;
    }

    // QUERY:
    // STRICT source from members table.
    // Fixed: 'username' does not exist. Using 'custom_name'.

    $sql = "
        SELECT 
            m.member_id,
            COALESCE(NULLIF(m.custom_name, ''), CONCAT('Member ', m.member_id)) as display_name,
            COALESCE(ms.page_views, 0) as page_views,
            COALESCE(ms.topic_votes, 0) as topic_votes,
            COALESCE(ms.post_votes, 0) as post_votes,
            (COALESCE(ms.topic_votes, 0) + COALESCE(ms.post_votes, 0)) as total_votes,
            COALESCE(ms.searches_made, 0) as searches
        FROM members m
        LEFT JOIN member_stats ms ON m.member_id = ms.member_id
        WHERE m.member_id > 0
          AND (
            COALESCE(ms.page_views, 0) > 0 OR 
            COALESCE(ms.topic_votes, 0) > 0 OR 
            COALESCE(ms.post_votes, 0) > 0 OR 
            COALESCE(ms.searches_made, 0) > 0
          )
        ORDER BY $orderBy
        LIMIT :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    $rank = 1;
    foreach ($results as $row) {
        $data[] = [
            'Rank' => $rank++,
            'MemberID' => (int) $row['member_id'],
            'Username' => htmlspecialchars($row['display_name']),
            'page_views' => (int) $row['page_views'],
            'topic_votes' => (int) $row['topic_votes'],
            'post_votes' => (int) $row['post_votes'],
            'total_votes' => (int) $row['total_votes'],
            'searches' => (int) $row['searches']
        ];
    }

    echo json_encode(['data' => $data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>