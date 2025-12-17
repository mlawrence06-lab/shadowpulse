<?php
// top_lists.php
// Returns Top lists for Members (Authors), Boards, Topics, and Posts.

require_once 'cors.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$limit = isset($_GET['limit']) ? min((int) $_GET['limit'], 100) : 20;

try {
    $pdo = sp_get_pdo();
    $data = [];

    switch ($action) {
        case 'members':
            // Optimized: Use stats_profiles for O(1) reads
            // We join content_metadata just to grab the latest known name for this ID
            $stmt = $pdo->prepare("
                SELECT sp.member_id, sp.vote_count, sp.total_score,
                       (SELECT author_name FROM content_metadata WHERE author_id = sp.member_id LIMIT 1) as author_name
                FROM stats_profiles sp
                ORDER BY (sp.total_score / NULLIF(sp.vote_count, 0)) DESC
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
                    'score' => round($avg, 2)
                ];
            }
            break;

        case 'boards':
            // Optimized: Use stats_boards for O(1) reads
            $stmt = $pdo->prepare("
                SELECT sb.board_id, sb.vote_count, sb.total_score, b.board_name
                FROM stats_boards sb
                LEFT JOIN boards b ON sb.board_id = b.board_id
                ORDER BY (sb.total_score / NULLIF(sb.vote_count, 0)) DESC
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
                    'score' => round($avg, 2)
                ];
            }
            break;

        case 'topics':
            // Optimized: Use stats_topics
            $stmt = $pdo->prepare("
                SELECT st.topic_id, st.vote_count, st.total_score, ti.topic_title
                FROM stats_topics st
                LEFT JOIN topics_info ti ON st.topic_id = ti.topic_id
                ORDER BY (st.total_score / NULLIF(st.vote_count, 0)) DESC
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

                $title = isset($r['topic_title']) ? $r['topic_title'] : ("Topic " . $topicId);

                $data[] = [
                    'id' => $topicId,
                    'label' => $title,
                    'count' => $count,
                    'score' => round($avg, 2)
                ];
            }
            break;

        case 'posts':
            require_once __DIR__ . '/ninja_helper.php';

            // Optimized: Use stats_posts
            $stmt = $pdo->prepare("
                SELECT sp.post_id, sp.vote_count, sp.total_score, 
                       cm.author_id, cm.author_name, cm.topic_id
                FROM stats_posts sp
                LEFT JOIN content_metadata cm ON sp.post_id = cm.post_id
                ORDER BY (sp.total_score / NULLIF(sp.vote_count, 0)) DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                // Optimized: Removed inline metadata fetch loop to strictly enforce "One Database Call" rule.
                // If metadata is missing, it will display generic info until the background queue processes it.
                // if (empty($r['author_name']) || empty($r['topic_id'])) { ... } removed.

                $topicId = isset($r['topic_id']) ? (int) $r['topic_id'] : 0;
                $count = (int) $r['vote_count'];
                $total = (float) $r['total_score'];
                $avg = $count > 0 ? ($total / $count) : 0;

                // Formatting links: Include Author Name in Label
                $label = "Post " . $r['post_id'];
                $authorName = !empty($r['author_name']) ? $r['author_name'] : ("User " . $r['author_id']);

                if (!empty($r['author_name'])) {
                    $label .= " (" . $r['author_name'] . ")";
                } elseif (!empty($r['author_id'])) {
                    $label .= " (" . $r['author_id'] . ")";
                }

                $data[] = [
                    'id' => (int) $r['post_id'],
                    'label' => $label,
                    'topic_id' => $topicId,
                    'author_name' => $authorName,
                    'count' => $count,
                    'score' => round($avg, 2)
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