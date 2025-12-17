<?php
// queue_runner.php - Processes one batch of Ninja Queue items
// Intended to be piggybacked onto other requests or run via cron.

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../v1/ninja_helper.php';

function run_ninja_queue($pdo)
{
    // 1. Throttle Check (File-based simple lock to avoid DB hits if heavily hit)
    $lockFile = sys_get_temp_dir() . '/sp_queue_last_run.txt';
    $lastRun = @file_get_contents($lockFile);
    if ($lastRun && (time() - intval($lastRun) < 5)) {
        return; // Run at most every 5 seconds
    }

    // Update lock
    file_put_contents($lockFile, time());

    // 2. Fetch One Pending Item
    // We prioritize older items (fifo)
    $stmt = $pdo->query("SELECT topic_id, last_post_id FROM ninja_queue WHERE status = 'pending' ORDER BY last_run ASC, created_at ASC LIMIT 1");
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item)
        return;

    $topicId = (int) $item['topic_id'];
    $lastId = (int) $item['last_post_id'];

    // 3. Update 'last_run' to prevent others grabbing it immediately (poor man's lock)
    $pdo->prepare("UPDATE ninja_queue SET last_run = NOW() WHERE topic_id = ?")->execute([$topicId]);

    // 4. Fetch Next Batch
    // Ninja API: Use order=ASC for chronological (Oldest First). Sort param is not supported.
    $url = "https://api.ninjastic.space/posts?topic_id={$topicId}&limit=200&order=ASC&last={$lastId}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ShadowPulse/0.1');
    $res = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($res, true);
    $posts = [];
    if (isset($json['data']['posts'])) {
        $posts = $json['data']['posts'];
    }

    if (count($posts) > 0) {
        // Save
        bulk_save_metadata($pdo, $posts);

        // Update Cursor
        $newLast = end($posts);
        $newLastId = isset($newLast['post_id']) ? (int) $newLast['post_id'] : (isset($newLast['id']) ? (int) $newLast['id'] : 0);

        if ($newLastId > $lastId) {
            // More to come? If we got full page, assume yes.
            // Or strictly: check if count < 200 via logic?
            // User snippet: "returned the last 26 posts" -> implies count < limit

            if (count($posts) < 200) {
                mark_ninja_queue_complete($pdo, $topicId);
            } else {
                upsert_ninja_queue($pdo, $topicId, $newLastId);
            }
        }
    } else {
        // No more posts?
        mark_ninja_queue_complete($pdo, $topicId);
    }
}
?>