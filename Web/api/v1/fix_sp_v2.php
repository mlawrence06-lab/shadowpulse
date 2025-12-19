<?php
// fix_sp_v2.php
header('Content-Type: text/plain');
require __DIR__ . '/../../config/db.php';

try {
    $pdo = sp_get_pdo();
    echo "Connected.\n";

    // 3. Drop and Recreate Stored Procedure with 4 params
    $spSQL = <<<SQL
DROP PROCEDURE IF EXISTS shadowpulse_get_page_context;

CREATE PROCEDURE shadowpulse_get_page_context(
    IN p_uuid VARCHAR(64),
    IN p_category VARCHAR(16),
    IN p_target_id BIGINT UNSIGNED,
    IN p_skip_view TINYINT
)
BEGIN
    DECLARE v_member_id BIGINT UNSIGNED DEFAULT 0;
    
    -- 1. Resolve Member ID
    SELECT member_id INTO v_member_id FROM members WHERE member_uuid = p_uuid LIMIT 1;

    -- PAGE VIEW TRACKING (Upsert)
    -- Only if target is valid and skip_view is false (0)
    IF p_target_id > 0 AND p_skip_view = 0 THEN
        IF p_category = 'topic' THEN
            INSERT INTO stats_topics (topic_id, page_views) VALUES (p_target_id, 1)
            ON DUPLICATE KEY UPDATE page_views = page_views + 1;
            
        ELSEIF p_category = 'post' THEN
            INSERT INTO stats_posts (post_id, page_views) VALUES (p_target_id, 1)
            ON DUPLICATE KEY UPDATE page_views = page_views + 1;
            
        ELSEIF p_category = 'board' THEN
            INSERT INTO stats_boards (board_id, page_views) VALUES (p_target_id, 1)
            ON DUPLICATE KEY UPDATE page_views = page_views + 1;
            
        ELSEIF p_category = 'profile' THEN
            INSERT INTO stats_profiles (member_id, page_views) VALUES (p_target_id, 1)
            ON DUPLICATE KEY UPDATE page_views = page_views + 1;
        END IF;
    END IF;
    
    -- RESULT SET 1: Member Stats
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
        ms.restore_ack,
        COALESCE(ms.page_views, 0) as page_views
    FROM members m
    LEFT JOIN member_stats ms ON m.member_id = ms.member_id
    WHERE m.member_id = v_member_id;

    -- RESULT SET 2: Target Context
    SELECT 
        v.effective_value as user_effective,
        v.desired_value as user_desired,
        
        -- Aggregate Stats
        COALESCE(st.vote_count, sp.vote_count, spr.vote_count, sb.vote_count, 0) as vote_count,
        COALESCE(st.average_score, sp.average_score, spr.average_score, sb.average_score, 0) as average_score,
        
        -- Target Label
        COALESCE(
            ti.topic_title, 
            (SELECT author_name FROM content_metadata WHERE author_id = p_target_id LIMIT 1),
            spr.profile_name,
            NULL 
        ) as target_label,
        
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

END;
SQL;

    $pdo->exec($spSQL);
    echo "Stored Procedure Re-created Successfully.\n";

} catch (PDOException $e) {
    echo "FATAL: " . $e->getMessage();
}
