<?php
/**
 * ShadowPulse Metadata Helper
 * Handles looking up and storing content hierarchy (Post -> Topic -> Board).
 */

require_once __DIR__ . '/../../config/db.php';

function ensure_content_metadata($category, $target_id, $pdo)
{
    // We only care about enriching 'post' and 'topic' votes for now so they link up.
    // 'board' and 'profile' are top-level or handled differently.

    if ($category === 'post') {
        // 1. Check if exists
        $stmt = $pdo->prepare("SELECT id FROM content_metadata WHERE post_id = ?");
        $stmt->execute([$target_id]);
        if ($stmt->fetch()) {
            return; // Already exists
        }

        // 2. STUB: Fetch from API
        // https://api.ninjastic.space/posts?post_id=...
        $url = "https://api.ninjastic.space/posts?post_id=" . intval($target_id) . "&limit=1";
        $data = fetch_json_via_curl($url);

        $topic_id = 0;
        $author_id = 0;
        $board_id = 0;

        if (isset($data['result']) && $data['result'] === 'success' && !empty($data['data']['posts'])) {
            $post = $data['data']['posts'][0];
            $topic_id = isset($post['topic_id']) ? (int) $post['topic_id'] : 0;
            $author_id = isset($post['author_uid']) ? (int) $post['author_uid'] : 0;
            $board_id = isset($post['board_id']) ? (int) $post['board_id'] : 0;
        }

        if ($topic_id > 0) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO content_metadata (post_id, topic_id, board_id, author_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$target_id, $topic_id, $board_id, $author_id]);
        }
    }

    if ($category === 'topic') {
        // 1. Check if exists
        $stmt = $pdo->prepare("SELECT id FROM content_metadata WHERE topic_id = ?");
        $stmt->execute([$target_id]);
        if ($stmt->fetch()) {
            return; // Already exists
        }

        // 2. STUB: Fetch from API
        $url = "https://api.ninjastic.space/posts?topic_id=" . intval($target_id) . "&limit=1";
        $data = fetch_json_via_curl($url);

        $board_id = 0;
        $author_id = 0; // OP of the topic

        if (isset($data['result']) && $data['result'] === 'success' && !empty($data['data']['posts'])) {
            $post = $data['data']['posts'][0];
            $board_id = isset($post['board_id']) ? (int) $post['board_id'] : 0;
            $author_id = isset($post['author_uid']) ? (int) $post['author_uid'] : 0;
        }

        if ($board_id > 0) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO content_metadata (topic_id, board_id, author_id) VALUES (?, ?, ?)");
            $stmt->execute([$target_id, $board_id, $author_id]);
        }
    }
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
