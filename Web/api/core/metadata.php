<?php
/**
 * ShadowPulse Metadata Helper
 * Handles looking up and storing content hierarchy (Post -> Topic -> Board).
 */

require_once __DIR__ . '/../../config/db.php';

function ensure_content_metadata($category, $target_id, $pdo, $topicIdHint = 0)
{
    // We only care about enriching 'post' and 'topic' votes for now so they link up.
    // 'board' and 'profile' are top-level or handled differently.

    $api_hit = false;
    $lookupTopicId = 0;
    $lookupPostId = 0;

    // Determine what we are looking up
    if ($category === 'topic') {
        $lookupTopicId = $target_id;
    } elseif ($category === 'post') {
        $lookupPostId = $target_id;
        // We might validly have a topic hint, but we prioritize looking up the post itself if possible.
        // However, if we need Topic Info, we might need a separate call or the Post response includes it.
    } else {
        return ['api_hit' => false, 'reason' => 'Not handled category'];
    }

    // 1. Check if metadata exists
    $stmt = $pdo->prepare("SELECT id FROM content_metadata WHERE " . ($category === 'post' ? 'post_id' : 'topic_id') . " = ?");
    $stmt->execute([$target_id]);
    if ($stmt->fetch()) {
        return ['api_hit' => false, 'reason' => 'Already exists'];
    }

    // 2. Fetch Metadata from Ninja API
    // If it's a POST vote, we query by ID to get the specific post (and its author).
    // If it's a TOPIC vote, we query by TOPIC_ID.

    $url = "";
    if ($category === 'post') {
        // Correct endpoint for specific post: https://api.ninjastic.space/posts/XYZ
        $url = "https://api.ninjastic.space/posts/" . intval($lookupPostId);
    } else {
        $url = "https://api.ninjastic.space/posts?topic_id=" . intval($lookupTopicId) . "&limit=1&order=created_at&sort=asc";
    }

    $data = fetch_json_via_curl($url);

    $board_id = 0;
    $author_id = 0;
    $topic_title = null;
    $author_name = null;
    $derivedTopicId = 0;

    if (isset($data['result']) && $data['result'] === 'success') {
        $post = null;
        if ($category === 'post' && !empty($data['data'])) {
            // For /posts/ID, data is the array of posts directly
            $post = is_array($data['data']) && isset($data['data'][0]) ? $data['data'][0] : null;
        } elseif ($category === 'topic' && !empty($data['data']['posts'])) {
            // For /posts?topic_id=..., data['posts'] is the array
            $post = $data['data']['posts'][0];
        }

        if ($post) {
            $board_id = isset($post['board_id']) ? (int) $post['board_id'] : 0;
            $author_id = isset($post['author_uid']) ? (int) $post['author_uid'] : 0;

            // For 'post' lookups, the Ninja API response might contain the topic_id
            if (isset($post['topic_id'])) {
                $derivedTopicId = (int) $post['topic_id'];
            }

            // Capture Names
            if (isset($post['subject'])) {
                $rawTitle = strip_tags($post['subject']);
                // Remove "Re: " prefix if present (case-insensitive)
                $topic_title = preg_replace('/^Re:\s*/i', '', $rawTitle);
            }
            if (isset($post['author']))
                $author_name = strip_tags($post['author']);
        }
    }

    // Fallback if API didn't give topic_id (which it should)
    if ($derivedTopicId === 0 && $topicIdHint > 0) {
        $derivedTopicId = $topicIdHint;
    }

    if ($board_id > 0) {
        if ($category === 'topic') {
            $stmt = $pdo->prepare("INSERT IGNORE INTO content_metadata (topic_id, board_id, author_id, topic_title, author_name) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$target_id, $board_id, $author_id, $topic_title, $author_name]);
        } elseif ($category === 'post') {
            // For post, we save post_id AND the linked topic_id
            $stmt = $pdo->prepare("INSERT IGNORE INTO content_metadata (post_id, topic_id, board_id, author_id, topic_title, author_name) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$target_id, $derivedTopicId, $board_id, $author_id, $topic_title, $author_name]);
        }
        return ['api_hit' => true, 'category' => $category, 'derived_topic' => $derivedTopicId];
    }

    if ($board_id > 0) {
        if ($category === 'topic') {
            $stmt = $pdo->prepare("INSERT IGNORE INTO content_metadata (topic_id, board_id, author_id, topic_title, author_name) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$target_id, $board_id, $author_id, $topic_title, $author_name]);
        } elseif ($category === 'post') {
            // For post, we save post_id AND the linked topic_id
            $stmt = $pdo->prepare("INSERT IGNORE INTO content_metadata (post_id, topic_id, board_id, author_id, topic_title, author_name) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$target_id, $lookupTopicId, $board_id, $author_id, $topic_title, $author_name]);
        }
        return ['api_hit' => true, 'category' => $category, 'linked_topic' => $lookupTopicId];
    }

    return ['api_hit' => true, 'reason' => 'API returned no valid data'];
}

function fetch_json_via_curl($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ShadowPulse/0.1');
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}
?>