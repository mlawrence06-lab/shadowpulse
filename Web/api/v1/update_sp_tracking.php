<?php
// update_sp_tracking.php
// Updates shadowpulse_get_page_context to v0.35 Integration (Tracking Enabled)
require __DIR__ . '/../../config/db.php';
header('Content-Type: text/plain');

try {
    $pdo = sp_get_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Updating Stored Procedure for v0.35 (Integrated Tracking)...\n";

    $sql = "
    DROP PROCEDURE IF EXISTS shadowpulse_get_page_context;
    
    CREATE PROCEDURE shadowpulse_get_page_context(
        IN p_uuid VARCHAR(64),
        IN p_category VARCHAR(16),
        IN p_target_id BIGINT UNSIGNED,
        IN p_title VARCHAR(255)
    )
    BEGIN
        DECLARE v_member_id BIGINT UNSIGNED DEFAULT 0;
        
        -- 1. Auto-Create User (Idempotent)
        INSERT IGNORE INTO members (member_uuid) VALUES (p_uuid);
        SELECT member_id INTO v_member_id FROM members WHERE member_uuid = p_uuid LIMIT 1;
        
        -- 2. TRACKING INTEGRATION (v0.35)
        -- A) Track User Activity (Viewer)
        IF v_member_id > 0 THEN
            INSERT INTO member_stats (member_id, page_views, last_active) 
            VALUES (v_member_id, 1, NOW())
            ON DUPLICATE KEY UPDATE 
                page_views = page_views + 1,
                last_active = NOW();
        END IF;

        -- B) Track Content Views (Target)
        -- Only if we have a valid target ID
        IF p_target_id > 0 THEN
            
            IF p_category = 'board' THEN
                INSERT INTO stats_boards (board_id, page_views) VALUES (p_target_id, 1)
                ON DUPLICATE KEY UPDATE page_views = page_views + 1;
            
            ELSEIF p_category = 'topic' THEN
                INSERT INTO stats_topics (topic_id, page_views) VALUES (p_target_id, 1)
                ON DUPLICATE KEY UPDATE page_views = page_views + 1;
                
                -- Capture Title if provided
                IF p_title IS NOT NULL AND p_title != '' THEN
                   INSERT INTO topics_info (topic_id, topic_title) VALUES (p_target_id, p_title)
                   ON DUPLICATE KEY UPDATE topic_title = p_title;
                END IF;

            ELSEIF p_category = 'post' THEN
                INSERT INTO stats_posts (post_id, page_views) VALUES (p_target_id, 1)
                ON DUPLICATE KEY UPDATE page_views = page_views + 1;

                -- Also capture Topic Title for Post context if possible? 
                -- Assuming p_title sent for 'post' is also the Topic Title.
                -- We can't easily link Post -> Topic here without topic_id.
                -- But usually we visit a topic.
                -- If we are visiting a POST directly, we might not know the topic ID unless passed.
                -- For now, ignore title update for posts unless we want to look up topic.

            ELSEIF p_category = 'profile' THEN
                INSERT INTO stats_profiles (member_id, page_views) VALUES (p_target_id, 1)
                ON DUPLICATE KEY UPDATE page_views = page_views + 1;

            END IF;

        END IF;

        -- RESULT 1: Member Stats
        SELECT 
            m.member_id,
            COALESCE(ms.topic_votes, 0) as topic_votes,
            COALESCE(ms.post_votes, 0) as post_votes,
            (COALESCE(ms.topic_votes, 0) + COALESCE(ms.post_votes, 0)) as total_points,
            CASE 
                WHEN (COALESCE(ms.topic_votes, 0) + COALESCE(ms.post_votes, 0)) > 100 THEN 5
                WHEN (COALESCE(ms.topic_votes, 0) + COALESCE(ms.post_votes, 0)) > 50 THEN 4
                WHEN (COALESCE(ms.topic_votes, 0) + COALESCE(ms.post_votes, 0)) > 20 THEN 3
                WHEN (COALESCE(ms.topic_votes, 0) + COALESCE(ms.post_votes, 0)) > 5 THEN 2
                ELSE 1
            END as calculated_rank,
            m.restore_ack,
            COALESCE(ms.page_views, 0) as page_views
        FROM members m
        LEFT JOIN member_stats ms ON m.member_id = ms.member_id
        WHERE m.member_id = v_member_id;

        -- RESULT 2: Context
        SELECT 
            v.effective_value as user_effective,
            v.desired_value as user_desired,
            COALESCE(st.vote_count, sp.vote_count, spr.vote_count, sb.vote_count, 0) as vote_count,
            COALESCE(st.average_score, sp.average_score, spr.average_score, sb.average_score, 0) as average_score,
            COALESCE(ti.topic_title, (SELECT author_name FROM content_metadata WHERE author_id = p_target_id LIMIT 1), spr.profile_name, NULL) as target_label,
            p_category as category,
            p_target_id as target_id
        FROM (SELECT 1) as dummy
        LEFT JOIN votes v ON v.target_id = p_target_id AND v.vote_category = p_category AND v.member_id = v_member_id
        LEFT JOIN stats_topics st ON p_category = 'topic' AND st.topic_id = p_target_id
        LEFT JOIN topics_info ti ON p_category = 'topic' AND ti.topic_id = p_target_id
        LEFT JOIN stats_posts sp ON p_category = 'post' AND sp.post_id = p_target_id
        LEFT JOIN stats_profiles spr ON p_category = 'profile' AND spr.member_id = p_target_id
        LEFT JOIN stats_boards sb ON p_category = 'board' AND sb.board_id = p_target_id
        LIMIT 1;

        -- RESULT 3: BTC (Fallback)
        SELECT NULL as close_price LIMIT 0;

    END;
    ";

    $pdo->exec($sql);
    echo "Success: Stored Procedure Updated.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
