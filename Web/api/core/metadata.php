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
        $stmt->closeCursor();
        return ['api_hit' => false, 'reason' => 'Already exists'];
    }
    $stmt->closeCursor();

    // 2. Fetch Metadata from Ninja API
    require_once __DIR__ . '/../v1/ninja_helper.php';

    // STRATEGY (Approved by User): 
    // 1. Fetch fast (Limit 1) to get Title/Board/Context immediately and unblock user.
    // 2. Queue the Topic for full background scraping.

    $fetchUrl = "";
    $scrapeTopicId = ($category === 'topic') ? $target_id : $topicIdHint;

    // Always fetch just ONE item first to be fast
    if ($scrapeTopicId > 0) {
        // Ninja API does not support sort/order params correctly or they changed. 
        // Using limit=1 returns a result (likely latest or oldest depending on default).
        // Suffices for getting board_id.
        $fetchUrl = "https://api.ninjastic.space/posts?topic_id=" . intval($scrapeTopicId) . "&limit=1";
    } elseif ($category === 'post') {
        $fetchUrl = "https://api.ninjastic.space/posts/" . intval($target_id);
    }

    $data = fetch_json_via_curl($fetchUrl);

    $postsFound = [];
    if (isset($data['result']) && $data['result'] === 'success') {
        if (isset($data['data']['posts'])) {
            $postsFound = $data['data']['posts'];
        } elseif (isset($data['data']) && is_array($data['data'])) {
            $postsFound = $data['data'];
        }
    }

    // BULK SAVE (Even if it's just 1)
    bulk_save_metadata($pdo, $postsFound);

    // QUEUE LOGIC:
    // If we successfully found data, queue the topic for background scraping.
    // If we fetched a post, we likely have the topic_id now.

    $queueTopicId = $scrapeTopicId;
    if ($queueTopicId == 0 && !empty($postsFound)) {
        // Try to extract topic_id from the post we found
        $first = reset($postsFound);
        if (isset($first['topic_id']))
            $queueTopicId = (int) $first['topic_id'];
    }

    if ($queueTopicId > 0) {
        // Upsert with last_post_id = 0? Or the one we found?
        // If we set to 0, the runner will fetch page 1 (200 items) again. This is redundant but safe.
        // If we set to the ID we found, the runner fetches everything AFTER it.
        // Let's set to the ID we found to avoid re-fetching the first post.

        $cursor = 0;
        if (!empty($postsFound)) {
            $lastItem = end($postsFound);
            $cursor = isset($lastItem['id']) ? (int) $lastItem['id'] : 0;
        }

        upsert_ninja_queue($pdo, $queueTopicId, $cursor);
    }

    return ['api_hit' => true, 'count' => count($postsFound)];
}

function fetch_json_via_curl($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}
?>