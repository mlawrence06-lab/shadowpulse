<?php
// ninja_helper.php
// Helper to fetch and cache topic titles from Ninjastic API

function get_ninja_topic_title($pdo, $topic_id)
{
    if ($topic_id <= 0)
        return null;

    // 1. Check Local DB (topics_info)
    $stmt = $pdo->prepare("SELECT topic_title FROM topics_info WHERE topic_id = ?");
    $stmt->execute([$topic_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row['topic_title'])) {
        return $row['topic_title'];
    }

    // 2. Fetch from API
    $title = fetch_title_from_api($topic_id);

    // 3. Store in DB (even if null/empty to avoid spamming API on failures? 
    //    Strategy: If API returns success, store. If not, maybe store "Topic X" temporarily?
    //    For now, only store if we got a string.)
    // 3. Store in DB
    if ($title) {
        $upsert = $pdo->prepare("
            INSERT INTO topics_info (topic_id, topic_title) 
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE topic_title = VALUES(topic_title)
        ");
        $upsert->execute([$topic_id, $title]);

        // 4. Trigger Scan Queue (Background processing for large topics)
        upsert_ninja_queue($pdo, $topic_id, 0);
    }

    return $title;
}

function fetch_title_from_api($topic_id)
{
    $url = "https://api.ninjastic.space/posts?topic_id=" . (int) $topic_id . "&limit=1";
    // NOTE: The user's example URL was https://api.ninjastic.space/posts?topic_id=5483310&limit=1
    // Let's verify the response structure from test_ninja.php previously viewed? 
    // Actually, I should use the exact URL structure

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    // Fix for potential SSL/Redir issues on shared hosting
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $res) {
        $json = json_decode($res, true);

        // Handle {"result": "success", "data": {"posts": [...]}} wrapper
        if (isset($json['data']['posts'])) {
            $json = $json['data']['posts'];
        }

        if (is_array($json) && count($json) > 0) {
            // First item
            $post = $json[0];

            if (isset($post['topic_title']) && !empty($post['topic_title'])) {
                return $post['topic_title'];
            }

            // Fallback to 'title' and strip "Re: "
            if (isset($post['title']) && !empty($post['title'])) {
                return preg_replace('/^Re:\s*/i', '', $post['title']);
            }
        }
    }
    return null;
}

/**
 * Bulk save metadata from Ninja API Posts array
 */
function bulk_save_metadata($pdo, $posts)
{
    if (empty($posts) || !is_array($posts))
        return;

    $stmtMeta = $pdo->prepare("INSERT IGNORE INTO content_metadata 
        (post_id, topic_id, board_id, author_id, author_name) 
        VALUES (:pid, :tid, :bid, :aid, :aname)");

    $stmtTopic = $pdo->prepare("INSERT INTO topics_info (topic_id, topic_title) 
        VALUES (:tid, :title) ON DUPLICATE KEY UPDATE topic_title = VALUES(topic_title)");

    foreach ($posts as $post) {
        $pid = isset($post['post_id']) ? (int) $post['post_id'] : (isset($post['id']) ? (int) $post['id'] : 0);
        $tid = isset($post['topic_id']) ? (int) $post['topic_id'] : 0;
        $bid = isset($post['board_id']) ? (int) $post['board_id'] : 0;
        $aid = isset($post['author_uid']) ? (int) $post['author_uid'] :
            (isset($post['uid']) ? (int) $post['uid'] :
                (isset($post['user_id']) ? (int) $post['user_id'] : 0));

        $rawTitle = isset($post['subject']) ? strip_tags($post['subject']) : (isset($post['title']) ? strip_tags($post['title']) : '');
        $cleanTitle = preg_replace('/^Re:\s*/i', '', $rawTitle);

        $authorName = isset($post['author']) ? strip_tags($post['author']) : '';

        // Save Topic Title if present
        if ($tid > 0 && !empty($cleanTitle)) {
            $stmtTopic->execute([':tid' => $tid, ':title' => $cleanTitle]);
        }

        // Save Post Metadata
        if ($pid > 0 && $tid > 0) {
            // Updated to UPSERT to fix missing data if record existed
            $stmtMetaUpsert = $pdo->prepare("
                INSERT INTO content_metadata (post_id, topic_id, board_id, author_id, author_name) 
                VALUES (:pid, :tid, :bid, :aid, :aname)
                ON DUPLICATE KEY UPDATE 
                    topic_id = VALUES(topic_id),
                    board_id = VALUES(board_id),
                    author_id = VALUES(author_id),
                    author_name = VALUES(author_name)
            ");
            $stmtMetaUpsert->execute([
                ':pid' => $pid,
                ':tid' => $tid,
                ':bid' => $bid,
                ':aid' => $aid,
                ':aname' => $authorName
            ]);
        }
    }
    $stmtMeta->closeCursor();
    $stmtTopic->closeCursor();
}

function upsert_ninja_queue($pdo, $topic_id, $last_post_id)
{
    if ($topic_id <= 0)
        return;

    // Status is pending if we are upserting (implying more work or start)
    $stmt = $pdo->prepare("
        INSERT INTO ninja_queue (topic_id, last_post_id, status) 
        VALUES (?, ?, 'pending') 
        ON DUPLICATE KEY UPDATE 
            last_post_id = VALUES(last_post_id),
            status = 'pending'
    ");
    $stmt->execute([$topic_id, $last_post_id]);
    $stmt->closeCursor();
}

function mark_ninja_queue_complete($pdo, $topic_id)
{
    $stmt = $pdo->prepare("UPDATE ninja_queue SET status = 'complete' WHERE topic_id = ?");
    $stmt->execute([$topic_id]);
}

/**
 * Fetch Post Info from API and cache it
 */
function get_ninja_post_info($pdo, $post_id)
{
    if ($post_id <= 0)
        return null;

    // 1. Check Local DB first (content_metadata)
    $stmt = $pdo->prepare("SELECT author_name, topic_id FROM content_metadata WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row['author_name'])) {
        return $row;
    }

    // 2. Fetch from API
    // Endpoint: https://api.ninjastic.space/posts/123
    $url = "https://api.ninjastic.space/posts/" . (int) $post_id;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $res) {
        $json = json_decode($res, true);
        if (is_array($json)) {
            // Logic to find exact post (since query by id likely returns one specific post, usually wrapped in data or direct?)
            // Based on Ninjastic docs/usage: it usually returns a list or paginated object.
            // If query by id, it might return array of 1.

            // Check if it's inside data.posts like the other endpoint
            $posts = [];
            if (isset($json['data'])) {
                echo "Data is set.\n";
                if (is_array($json['data'])) {
                    echo "Data is Array.\n";
                    // Check if 'data' ITSELF is the list of posts (api/posts/ID returns this)
                    if (isset($json['data'][0]) && (isset($json['data'][0]['post_id']) || isset($json['data'][0]['id']))) {
                        echo "Parsing Match: Direct Data Array.\n";
                        $posts = $json['data'];
                    }
                    // Or if it's wrapped in data.posts (api/posts?topic_id=X returns this)
                    elseif (isset($json['data']['posts'])) {
                        echo "Parsing Match: Data.Posts.\n";
                        $posts = $json['data']['posts'];
                    } else {
                        echo "Parsing: No Match inside Data.\n";
                    }
                } else {
                    echo "Data is NOT Array.\n";
                }
            } elseif (isset($json['posts']))
                $posts = $json['posts'];
            elseif (isset($json['id']) || isset($json['post_id']))
                $posts = [$json]; // Direct object
            else {
                // Maybe it's a list directly
                if (isset($json[0]['id']) || isset($json[0]['post_id']))
                    $posts = $json;
            }

            if (!empty($posts)) {
                // Save Metadata
                bulk_save_metadata($pdo, $posts);

                // Return the data for this specific post
                foreach ($posts as $p) {
                    $pid = isset($p['post_id']) ? (int) $p['post_id'] : (isset($p['id']) ? (int) $p['id'] : 0);
                    echo "Check: $pid vs $post_id\n";
                    if ($pid == $post_id) {
                        return [
                            'author_name' => isset($p['author']) ? strip_tags($p['author']) : '',
                            'topic_id' => isset($p['topic_id']) ? (int) $p['topic_id'] : 0,
                            'author_uid' => isset($p['author_uid']) ? (int) $p['author_uid'] : 0
                        ];
                    }
                }
            }
        }
    }
    return null;
}
?>