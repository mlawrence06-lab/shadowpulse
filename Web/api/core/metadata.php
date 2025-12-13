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

    // Determine what Topic ID to lookup metadata for
    if ($category === 'topic') {
        // If voting on a topic, the target is the topic
        $lookupTopicId = $target_id;
    } elseif ($category === 'post') {
        // If voting on a post, we need the Topic ID hint to fetch metadata
        if ($topicIdHint > 0) {
            $lookupTopicId = $topicIdHint;
        } else {
            return ['api_hit' => false, 'reason' => 'Post vote without topic hint'];
        }
    } else {
        return ['api_hit' => false, 'reason' => 'Not handled category'];
    }

    // 1. Check if metadata for this Target ID (post or topic) already exists
    // (We link the Target ID to the Metadata fields)
    $stmt = $pdo->prepare("SELECT id FROM content_metadata WHERE " . ($category === 'post' ? 'post_id' : 'topic_id') . " = ?");
    $stmt->execute([$target_id]);
    if ($stmt->fetch()) {
        return ['api_hit' => false, 'reason' => 'Already exists'];
    }

    // 2. Fetch Metadata from Ninja API using the TOPIC ID
    // (Even for a post, we fetch the Topic info to get Board/Title)
    $url = "https://api.ninjastic.space/posts?topic_id=" . intval($lookupTopicId) . "&limit=1";
    $data = fetch_json_via_curl($url);

    $board_id = 0;
    $author_id = 0; // OP of the topic (or author of the post? Ideally author of the post if we could look it up)
    $topic_title = null;
    $author_name = null;

    if (isset($data['result']) && $data['result'] === 'success' && !empty($data['data']['posts'])) {
        $post = $data['data']['posts'][0];
        $board_id = isset($post['board_id']) ? (int) $post['board_id'] : 0;

        // Note: For Post votes, we ideally want the Post Author.
        // But since we are querying by Topic ID, we get the Topic OP.
        // This is a limitation without a Post Lookup API.
        // However, we CAN capture the Topic Title and Board ID correctly.
        $author_id = isset($post['author_uid']) ? (int) $post['author_uid'] : 0;

        // Capture Names
        if (isset($post['subject']))
            $topic_title = strip_tags($post['subject']);
        if (isset($post['author']))
            $author_name = strip_tags($post['author']);
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