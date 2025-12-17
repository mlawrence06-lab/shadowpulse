DELIMITER $$

DROP PROCEDURE IF EXISTS `shadowpulse_get_page_context`$$
CREATE PROCEDURE `shadowpulse_get_page_context`(
    IN p_uuid VARCHAR(64),
    IN p_category VARCHAR(16),
    IN p_target_id BIGINT UNSIGNED
)
BEGIN
    DECLARE v_member_id BIGINT UNSIGNED DEFAULT 0;
    
    -- 1. Resolve Member ID (Auto-Register)
    SELECT member_id INTO v_member_id FROM members WHERE member_uuid = p_uuid LIMIT 1;
    
    IF v_member_id = 0 OR v_member_id IS NULL THEN
        INSERT INTO members (member_uuid) VALUES (p_uuid);
        SET v_member_id = LAST_INSERT_ID();
        INSERT IGNORE INTO member_stats (member_id, page_views) VALUES (v_member_id, 1);
    ELSE
        -- Increment Page Views (Ensure 'last_active' exists via previous fix)
        INSERT INTO member_stats (member_id, page_views) VALUES (v_member_id, 1)
        ON DUPLICATE KEY UPDATE page_views = page_views + 1, last_active = NOW();
    END IF;
    
    -- RESULT SET 1: Member Stats
    SELECT 
        m.member_id,
        COALESCE(ms.page_views, 0) as page_views,
        COALESCE(ms.topic_votes, 0) as topic_votes,
        COALESCE(ms.post_votes, 0) as post_votes,
        (COALESCE(ms.topic_votes, 0) + COALESCE(ms.post_votes, 0)) as total_points,
        CASE 
            WHEN (COALESCE(ms.topic_votes, 0) + COALESCE(ms.post_votes, 0)) > 100 THEN 5
            WHEN (COALESCE(ms.topic_votes, 0) + COALESCE(ms.post_votes, 0)) > 50 THEN 4
            WHEN (COALESCE(ms.topic_votes, 0) + COALESCE(ms.post_votes, 0)) > 20 THEN 3
            WHEN (COALESCE(ms.topic_votes, 0) + COALESCE(ms.post_votes, 0)) > 5 THEN 2
            ELSE 1
        END as calculated_level,
        0 as restore_ack -- Placeholder for future logic
    FROM members m
    LEFT JOIN member_stats ms ON m.member_id = ms.member_id
    WHERE m.member_id = v_member_id;

    -- RESULT SET 2: Target Context
    SELECT 
        v.effective_value as user_effective,
        v.desired_value as user_desired,
        
        COALESCE(st.vote_count, sp.vote_count, spr.vote_count, sb.vote_count, 0) as vote_count,
        CASE 
             WHEN sb.vote_count > 0 THEN sb.total_score / sb.vote_count 
             ELSE COALESCE(st.average_score, sp.average_score, spr.average_score, 0)
        END as average_score,
        
        CASE 
            WHEN p_category = 'topic' THEN 
                (SELECT COUNT(*) + 1 FROM stats_topics st2 
                 WHERE st2.total_score > st.total_score 
                 OR (st2.total_score = st.total_score AND st2.topic_id < st.topic_id))
            WHEN p_category = 'post' THEN 
                (SELECT COUNT(*) + 1 FROM stats_posts sp2 
                 WHERE sp2.total_score > sp.total_score 
                 OR (sp2.total_score = sp.total_score AND sp2.post_id < sp.post_id))
            WHEN p_category = 'board' THEN
                (SELECT COUNT(*) + 1 FROM stats_boards sb2 
                 WHERE sb2.total_score > sb.total_score 
                 OR (sb2.total_score = sb.total_score AND sb2.board_id < sb.board_id))
            WHEN p_category = 'profile' THEN
                (SELECT COUNT(*) + 1 FROM stats_profiles spr2 
                 WHERE spr2.total_score > spr.total_score 
                 OR (spr2.total_score = spr.total_score AND spr2.member_id < spr.member_id))
            ELSE 0 
        END as item_rank,

        COALESCE(
            ti.topic_title, 
            (SELECT author_name FROM content_metadata WHERE author_id = p_target_id LIMIT 1), 
            spr.profile_name
        ) as target_label
        
    FROM (SELECT 1) as dummy
    LEFT JOIN votes v ON v.target_id = p_target_id AND v.vote_category = p_category AND v.member_id = v_member_id
    LEFT JOIN stats_topics st ON p_category = 'topic' AND st.topic_id = p_target_id
    LEFT JOIN topics_info ti ON p_category = 'topic' AND ti.topic_id = p_target_id
    LEFT JOIN stats_posts sp ON p_category = 'post' AND sp.post_id = p_target_id
    LEFT JOIN stats_profiles spr ON p_category = 'profile' AND spr.member_id = p_target_id
    LEFT JOIN stats_boards sb ON p_category = 'board' AND sb.board_id = p_target_id
    LIMIT 1;
    
    -- RESULT SET 3: BTC Price History
    SELECT close_price, candle_time 
    FROM btc_price_history 
    WHERE symbol = 'BTCUSDT' 
    ORDER BY candle_time DESC 
    LIMIT 60;

END$$
DELIMITER ;
