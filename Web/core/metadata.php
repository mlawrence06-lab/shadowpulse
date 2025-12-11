<?php
/**
 * ShadowPulse Metadata Helper
 * Handles looking up and storing content hierarchy (Post -> Topic -> Board).
 */

require_once __DIR__ . '/../db.php'; // adjusting path assuming this is in Web/core/

function ensure_content_metadata($category, $target_id, $pdo) {
    // We only care about enriching 'post' and 'topic' votes for now so they link up.
    // 'board' and 'profile' are top-level or handled differently.
    
    if ($category === 'post') {
        // 1. Check if exists
        $stmt = $pdo->prepare("SELECT id FROM content_metadata WHERE post_id = ?");
        $stmt->execute([$target_id]);
        if ($stmt->fetch()) {
            return; // Already exists
        }

        // 2. STUB: Fetch from API (simulate for now)
        // In real impl, we would curl https://bitcointalk.org/index.php?topic=...
        $simulated_topic_id = 0; 
        $simulated_author_id = 0;

        // For now, we insert placeholders or 0s if we can't find it. 
        // Logic: The Rank Tracking SQL is robust enough to handle nulls/0s (it just won't update the parent stat).
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO content_metadata (post_id, topic_id, author_id) VALUES (?, ?, ?)");
        $stmt->execute([$target_id, $simulated_topic_id, $simulated_author_id]);
    }
    
    if ($category === 'topic') {
        // 1. Check if exists
        $stmt = $pdo->prepare("SELECT id FROM content_metadata WHERE topic_id = ?");
        $stmt->execute([$target_id]);
        if ($stmt->fetch()) {
            return; // Already exists
        }

        // 2. STUB: Fetch from API
        $simulated_board_id = 0;

        $stmt = $pdo->prepare("INSERT IGNORE INTO content_metadata (topic_id, board_id) VALUES (?, ?)");
        $stmt->execute([$target_id, $simulated_board_id]);
    }
}
