<?php
// top_lists.php
// Returns Top lists for Members (Authors), Boards, Topics, and Posts.

require_once 'cors.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/ninja_helper.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$limit = isset($_GET['limit']) ? min((int) $_GET['limit'], 100) : 20;

try {
    $pdo = sp_get_pdo();
    $sort = $_GET['sort'] ?? 'score'; // 'score' or 'views'
    $data = [];

    // Helper to Determine Order Clause
    $OrderBy = "";
    $WhereClause = "";
    if ($sort === 'views') {
        $OrderBy = "ORDER BY page_views DESC";
    } else {
        $OrderBy = "ORDER BY (total_score / NULLIF(vote_count, 0)) DESC";
    }

    switch ($action) {
        case 'members':
            if ($sort === 'views') {
                $WhereClause = "WHERE sp.page_views > 0";
            } else {
                $WhereClause = "WHERE sp.vote_count > 0";
            }
            // Optimized: Use stats_profiles for O(1) reads
            $stmt = $pdo->prepare("
                SELECT sp.member_id, sp.vote_count, sp.total_score, sp.page_views,
                       (SELECT author_name FROM content_metadata WHERE author_id = sp.member_id LIMIT 1) as author_name
                FROM stats_profiles sp
                $WhereClause
                $OrderBy
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $r) {
                $count = (int) $r['vote_count'];
                $total = (float) $r['total_score'];
                $avg = $count > 0 ? ($total / $count) : 0;
                $name = !empty($r['author_name']) ? $r['author_name'] : ("User " . $r['member_id']);

                $data[] = [
                    'id' => (int) $r['member_id'],
                    'label' => (string) $name,
                    'count' => $count,
                    'score' => round($avg, 2),
                    'views' => (int) ($r['page_views'] ?? 0)
                ];
            }
            break;

        case 'boards':
            // Boards do not track page views. Return empty if sorting by views.
            // Boards do not track page views. Return empty if sorting by views.
            // FIXED: Boards DO track views via SP. Allowed.

            $stmt = $pdo->prepare("
                SELECT sb.board_id, sb.vote_count, sb.total_score, b.board_name, sb.page_views
                FROM stats_boards sb
                LEFT JOIN boards b ON sb.board_id = b.board_id
                WHERE sb.page_views > 0
                $OrderBy
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $r) {
                $id = (int) $r['board_id'];
                $label = !empty($r['board_name']) ? $r['board_name'] : ("Board " . $id);
                $count = (int) $r['vote_count'];
                $total = (float) $r['total_score'];
                $avg = $count > 0 ? ($total / $count) : 0;

                $data[] = [
                    'id' => $id,
                    'label' => $label,
                    'count' => $count,
                    'score' => round($avg, 2),
                    'views' => (int) ($r['page_views'] ?? 0)
                ];
            }
            break;

        case 'topics':
            if ($sort === 'views')
                $WhereClause = "WHERE st.page_views > 0";
            $stmt = $pdo->prepare("
                SELECT st.topic_id, st.vote_count, st.total_score, st.page_views, ti.topic_title
                FROM stats_topics st
                LEFT JOIN topics_info ti ON st.topic_id = ti.topic_id
                $WhereClause
                $OrderBy
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $r) {
                $topicId = (int) $r['topic_id'];
                $count = (int) $r['vote_count'];
                $total = (float) $r['total_score'];
                $avg = $count > 0 ? ($total / $count) : 0;
                $title = isset($r['topic_title']) ? $r['topic_title'] : null;

                if (empty($title)) {
                    $title = get_ninja_topic_title($pdo, $topicId); // API Fetch
                }

                if (empty($title)) {
                    $title = "Topic " . $topicId;
                }

                $data[] = [
                    'id' => $topicId,
                    'label' => $title,
                    'count' => $count,
                    'score' => round($avg, 2),
                    'views' => (int) ($r['page_views'] ?? 0)
                ];
            }
            break;

        case 'posts':
            if ($sort === 'views')
                $WhereClause = "WHERE sp.page_views > 0";
            $stmt = $pdo->prepare("
                SELECT sp.post_id, sp.vote_count, sp.total_score, sp.page_views,
                       cm.author_id, cm.author_name, cm.topic_id, cm.post_subject,
                       ti.topic_title
                FROM stats_posts sp
                LEFT JOIN content_metadata cm ON sp.post_id = cm.post_id
                LEFT JOIN topics_info ti ON cm.topic_id = ti.topic_id
                $WhereClause
                $OrderBy
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $topicId = isset($r['topic_id']) ? (int) $r['topic_id'] : 0;
                $postId = (int) $r['post_id'];

                // Fallback: If topic_id is missing, try to get it from Post Info (Ninja)
                $ninjaTitle = "";
                if ($topicId <= 0) {
                    $pInfo = get_ninja_post_info($pdo, $postId); // Fetches Author & Topic ID
                    // Manually update author name if found
                    if ($pInfo) {
                        if (!empty($pInfo['topic_id']))
                            $topicId = (int) $pInfo['topic_id'];
                        if (!empty($pInfo['author_name']))
                            $r['author_name'] = $pInfo['author_name'];
                        if (!empty($pInfo['title']))
                            $ninjaTitle = $pInfo['title'];
                    }
                }

                $topicTitle = $r['topic_title'] ?? '';

                // If we have a Topic ID but missing title, queue it for background repair
                if ($topicId > 0 && empty($topicTitle)) {
                    upsert_ninja_queue($pdo, $topicId, 0);
                }

                $count = (int) $r['vote_count'];
                $total = (float) $r['total_score'];
                $avg = $count > 0 ? ($total / $count) : 0;

                $label = "Post " . $postId;
                $authorName = !empty($r['author_name']) ? $r['author_name'] : ("User " . ($r['author_id'] ?? 0));

                $postSubject = $r['post_subject'] ?? '';

                if (!empty($ninjaTitle)) {
                    $label = $ninjaTitle;
                } elseif (!empty($postSubject)) {
                    $label = $postSubject;
                } elseif (!empty($topicTitle)) {
                    $label = $topicTitle . " (Post " . $postId . ")";
                } elseif ($topicId > 0) {
                    // Simple fallback if queue hasn't processed it yet
                    $label = "Topic " . $topicId . " (Post " . $postId . ")";
                }

                $data[] = [
                    'id' => (int) $r['post_id'],
                    'label' => $label,
                    'topic_id' => $topicId,
                    'author_name' => $authorName,
                    'count' => $count,
                    'score' => round($avg, 2),
                    'views' => (int) ($r['page_views'] ?? 0)
                ];
            }
            break;

        default:
            throw new Exception("Invalid action. Use members, boards, topics, or posts.");
    }

    echo json_encode(['ok' => true, 'data' => $data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
?>