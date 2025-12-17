DROP PROCEDURE IF EXISTS shadowpulse_get_page_context;

CREATE PROCEDURE shadowpulse_get_page_context(
    IN p_uuid VARCHAR(64),
    IN p_category VARCHAR(16),
    IN p_target_id BIGINT UNSIGNED
)
BEGIN
    DECLARE v_member_id BIGINT UNSIGNED DEFAULT 0;
    
    -- 1. Resolve Member ID
    SELECT member_id INTO v_member_id FROM members WHERE member_uuid = p_uuid LIMIT 1;
    
    -- RESULT SET 1: Member Stats (for Logo / User Profile)
    -- If member not found, strictly return 0s (frontend handles guest mode)
    SELECT 
        m.member_id,
        COALESCE(ms.topic_votes, 0) as topic_votes,
        COALESCE(ms.post_votes, 0) as post_votes,
        (COALESCE(ms.topic_votes, 0) + COALESCE(ms.post_votes, 0)) as total_points,
        -- Simple Rank approximation (e.g. based on points thresholds) for now
        CASE 
            WHEN (COALESCE(ms.topic_votes, 0) + COALESCE(ms.post_votes, 0)) > 100 THEN 5
            WHEN (COALESCE(ms.topic_votes, 0) + COALESCE(ms.post_votes, 0)) > 50 THEN 4
            WHEN (COALESCE(ms.topic_votes, 0) + COALESCE(ms.post_votes, 0)) > 20 THEN 3
            WHEN (COALESCE(ms.topic_votes, 0) + COALESCE(ms.post_votes, 0)) > 5 THEN 2
            ELSE 1
        END as calculated_rank
    FROM members m
    LEFT JOIN member_stats ms ON m.member_id = ms.member_id
    WHERE m.member_id = v_member_id;

    -- RESULT SET 2: Target Context (Vote Summary, User Vote, Label)
    SELECT 
        -- User Vote
        v.effective_value as user_effective,
        v.desired_value as user_desired,
        
        -- Aggregate Stats (Consolidated via COALESCE based on category)
        COALESCE(st.vote_count, sp.vote_count, spr.vote_count, 0) as vote_count,
        COALESCE(st.average_score, sp.average_score, spr.average_score, 0) as average_score,
        
        -- Target Label (Topic Title / Profile Name)
        COALESCE(
            ti.topic_title, 
            (SELECT author_name FROM content_metadata WHERE author_id = p_target_id LIMIT 1), -- For Profile (if not in stats yet)
            spr.profile_name
        ) as target_label,
        
        p_category as category,
        p_target_id as target_id
        
    FROM (SELECT 1) as dummy
    -- User Vote Join
    LEFT JOIN votes v ON v.target_id = p_target_id AND v.vote_category = p_category AND v.member_id = v_member_id
    
    -- Topic Stats & Info
    LEFT JOIN stats_topics st ON p_category = 'topic' AND st.topic_id = p_target_id
    LEFT JOIN topics_info ti ON p_category = 'topic' AND ti.topic_id = p_target_id
    
    -- Post Stats
    LEFT JOIN stats_posts sp ON p_category = 'post' AND sp.post_id = p_target_id
    
    -- Profile Stats
    LEFT JOIN stats_profiles spr ON p_category = 'profile' AND spr.member_id = p_target_id
    
    LIMIT 1;

END;
