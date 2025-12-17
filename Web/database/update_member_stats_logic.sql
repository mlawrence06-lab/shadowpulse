DELIMITER $$

-- 1. Update shadowpulse_get_page_context (Increment Page Views)
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
        -- Init stats
        INSERT IGNORE INTO member_stats (member_id, page_views) VALUES (v_member_id, 1);
    ELSE
        -- Increment Page Views
        INSERT INTO member_stats (member_id, page_views) VALUES (v_member_id, 1)
        ON DUPLICATE KEY UPDATE page_views = page_views + 1, last_active = NOW();
    END IF;
    
    -- RESULT SET 1: Member Stats (Member Level)
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
        END as calculated_level
    FROM members m
    LEFT JOIN member_stats ms ON m.member_id = ms.member_id
    WHERE m.member_id = v_member_id;

    -- RESULT SET 2: Target Context (Vote Summary, Rank, User Vote, Label)
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

END$$


-- 2. Update shadowpulse_cast_vote (Increment User Vote Counts)
DROP PROCEDURE IF EXISTS `shadowpulse_cast_vote`$$
CREATE PROCEDURE `shadowpulse_cast_vote`(
    IN `p_member_id` BIGINT UNSIGNED, 
    IN `p_vote_category` VARCHAR(16), 
    IN `p_target_id` BIGINT UNSIGNED, 
    IN `p_desired_value` TINYINT UNSIGNED, 
    OUT `p_effective_value` TINYINT UNSIGNED
)
BEGIN
  DECLARE v_cat enum ('topic', 'post', 'profile', 'board');
  DECLARE v_C int DEFAULT 0; 
  DECLARE v_M int DEFAULT 0; 
  DECLARE v_E int DEFAULT 0; 
  DECLARE v_M_cap int DEFAULT 0;
  DECLARE v_E_cap int DEFAULT 0;
  DECLARE v_vote_id bigint UNSIGNED;
  DECLARE v_eff tinyint UNSIGNED;
  DECLARE v_desired tinyint UNSIGNED;
  
  DECLARE v_old_eff TINYINT UNSIGNED;
  DECLARE v_delta INT;
  DECLARE v_loop_target_id BIGINT UNSIGNED;
  DECLARE v_new_eff TINYINT UNSIGNED;
  DECLARE v_is_new BOOLEAN DEFAULT FALSE;

  -- Cascade Variables
  DECLARE v_linked_id BIGINT UNSIGNED;

  SET v_cat = CASE LOWER(TRIM(p_vote_category)) 
    WHEN 'topic' THEN 'topic' WHEN 'post' THEN 'post' WHEN 'profile' THEN 'profile' WHEN 'board' THEN 'board' ELSE 'topic' 
  END;

  IF p_desired_value < 1 OR p_desired_value > 5 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'shadowpulse_cast_vote: desired_value must be between 1 and 5';
  END IF;

  START TRANSACTION;

    SELECT effective_value INTO v_old_eff FROM votes WHERE member_id = p_member_id AND vote_category = v_cat AND target_id = p_target_id;
    
    INSERT INTO votes (member_id, vote_category, target_id, desired_value, effective_value, created_at, updated_at)
      VALUES (p_member_id, v_cat, p_target_id, p_desired_value, p_desired_value, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      desired_value = VALUES(desired_value),
      effective_value = VALUES(effective_value),
      updated_at = VALUES(updated_at);
      
    IF v_old_eff IS NULL THEN
        -- New Vote
        SET v_is_new = TRUE;
        SET v_delta = p_desired_value;
        
        -- UPDATE MEMBER STATS (Increment count of votes CAST by this member)
        IF v_cat = 'topic' THEN
             UPDATE member_stats SET topic_votes = topic_votes + 1 WHERE member_id = p_member_id;
        ELSEIF v_cat = 'post' THEN
             UPDATE member_stats SET post_votes = post_votes + 1 WHERE member_id = p_member_id;
        END IF;
    ELSE
        -- Changed Vote
        SET v_is_new = FALSE;
        SET v_delta = CAST(p_desired_value AS SIGNED) - CAST(v_old_eff AS SIGNED);
    END IF;

    -- Update Primary Target Ranking
    IF v_delta <> 0 THEN
         CALL internal_update_rank(v_cat, p_target_id, v_delta, v_is_new);
         
         -- CASCADE LOGIC
         IF v_cat = 'post' THEN
             SELECT author_id INTO v_linked_id FROM content_metadata WHERE post_id = p_target_id LIMIT 1;
             IF v_linked_id IS NOT NULL AND v_linked_id > 0 THEN
                 CALL internal_update_rank('profile', v_linked_id, v_delta, v_is_new);
             END IF;
         ELSEIF v_cat = 'topic' THEN
             SELECT board_id INTO v_linked_id FROM content_metadata WHERE topic_id = p_target_id AND board_id > 0 LIMIT 1;
             IF v_linked_id IS NOT NULL THEN
                  CALL internal_update_rank('board', v_linked_id, v_delta, v_is_new);
             END IF;
         END IF;
    END IF;


    SELECT
      IFNULL(SUM(CASE WHEN effective_value = 3 THEN 1 ELSE 0 END), 0),
      IFNULL(SUM(CASE WHEN effective_value IN (2, 4) THEN 1 ELSE 0 END), 0),
      IFNULL(SUM(CASE WHEN effective_value IN (1, 5) THEN 1 ELSE 0 END), 0) INTO v_C, v_M, v_E
    FROM votes WHERE member_id = p_member_id AND vote_category = v_cat;

    SET v_M_cap = FLOOR(v_C / 2);
    SET v_E_cap = FLOOR(v_M / 2);

    SELECT effective_value INTO p_effective_value FROM votes WHERE member_id = p_member_id AND vote_category = v_cat AND target_id = p_target_id;
    
    INSERT INTO vote_logs (member_id, vote_category, target_id, desired_value, effective_value)
      VALUES (p_member_id, v_cat, p_target_id, p_desired_value, p_effective_value);

  COMMIT;
END$$

DELIMITER ;

-- Backfill Member Stats from existing votes
UPDATE member_stats ms
JOIN (
    SELECT member_id, 
           COUNT(CASE WHEN vote_category = 'topic' THEN 1 END) as tv,
           COUNT(CASE WHEN vote_category = 'post' THEN 1 END) as pv
    FROM votes 
    GROUP BY member_id
) v ON ms.member_id = v.member_id
SET ms.topic_votes = v.tv, ms.post_votes = v.pv;
