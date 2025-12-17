-- Modify SP to return Member Info (Bootstrap Data)
DROP PROCEDURE IF EXISTS shadowpulse_get_page_context;
DELIMITER //
CREATE PROCEDURE shadowpulse_get_page_context(
    IN p_member_uuid VARCHAR(64),
    IN p_category VARCHAR(32),
    IN p_target_id INT
)
BEGIN
   DECLARE v_member_id INT DEFAULT 0;
   DECLARE v_restore_ack TINYINT DEFAULT 0;
   
   -- 1. Resolve Member ID and Restore Ack
   IF (p_member_uuid != '') THEN
       SELECT member_id, restore_ack INTO v_member_id, v_restore_ack 
       FROM members WHERE member_uuid = p_member_uuid LIMIT 1;
       
       -- Register if new
       -- Register if new
       IF (v_member_id = 0) THEN
           INSERT INTO members (member_uuid) VALUES (p_member_uuid);
           SET v_member_id = LAST_INSERT_ID();
           SET v_restore_ack = 0;
       END IF;
   END IF;
   
   -- 2. Return Member Stats + Bootstrap Info (First Result Set)
   SELECT 
       1 as calculated_rank,         -- Placeholder
       0 as total_points,            -- Placeholder
       v_member_id as member_id,     -- NEW: Bootstrap ID
       v_restore_ack as restore_ack, -- NEW: Bootstrap Ack
       (SELECT COUNT(*) FROM votes WHERE member_id = v_member_id AND vote_category='topic') as topic_votes,
       (SELECT COUNT(*) FROM votes WHERE member_id = v_member_id AND vote_category='post') as post_votes;

   -- 3. Return Context (Second Result Set)
   IF (p_category = 'board') THEN
       SELECT 
           sb.vote_count,
           sb.total_score,
           (sb.total_score / CASE WHEN sb.vote_count > 0 THEN sb.vote_count ELSE 1 END) as average_score,
           (
               SELECT COUNT(*) + 1
               FROM stats_boards sb2
               WHERE (sb2.total_score / CASE WHEN sb2.vote_count > 0 THEN sb2.vote_count ELSE 1 END) > 
                     (sb.total_score / CASE WHEN sb.vote_count > 0 THEN sb.vote_count ELSE 1 END)
           ) as item_rank,
           b.board_name as target_label,
           -- User Vote
           (SELECT effective_value FROM votes WHERE member_id = v_member_id AND vote_category = 'board' AND target_id = p_target_id LIMIT 1) as user_effective,
           (SELECT desired_value FROM votes WHERE member_id = v_member_id AND vote_category = 'board' AND target_id = p_target_id LIMIT 1) as user_desired
       FROM stats_boards sb
       LEFT JOIN boards b ON sb.board_id = b.board_id
       WHERE sb.board_id = p_target_id;
       
   ELSEIF (p_category = 'topic') THEN
       SELECT 
           st.vote_count,
           st.total_score,
           (st.total_score / CASE WHEN st.vote_count > 0 THEN st.vote_count ELSE 1 END) as average_score,
           (
               SELECT COUNT(*) + 1
               FROM stats_topics st2
               WHERE (st2.total_score / CASE WHEN st2.vote_count > 0 THEN st2.vote_count ELSE 1 END) > 
                     (st.total_score / CASE WHEN st.vote_count > 0 THEN st.vote_count ELSE 1 END)
           ) as item_rank,
           -- Label (Currently no Topics table for titles? Return ID)
           CONCAT('Topic ', p_target_id) as target_label,
           -- User Vote
           (SELECT effective_value FROM votes WHERE member_id = v_member_id AND vote_category = 'topic' AND target_id = p_target_id LIMIT 1) as user_effective,
           (SELECT desired_value FROM votes WHERE member_id = v_member_id AND vote_category = 'topic' AND target_id = p_target_id LIMIT 1) as user_desired
       FROM stats_topics st
       WHERE st.topic_id = p_target_id;
       
    ELSEIF (p_category = 'post') THEN
        SELECT 
            sp.vote_count,
            sp.total_score,
            (sp.total_score / CASE WHEN sp.vote_count > 0 THEN sp.vote_count ELSE 1 END) as average_score,
            (
                SELECT COUNT(*) + 1
                FROM stats_posts sp2
                WHERE (sp2.total_score / CASE WHEN sp2.vote_count > 0 THEN sp2.vote_count ELSE 1 END) > 
                      (sp.total_score / CASE WHEN sp.vote_count > 0 THEN sp.vote_count ELSE 1 END)
            ) as item_rank,
            CONCAT('Post ', p_target_id) as target_label,
            -- User Vote
            (SELECT effective_value FROM votes WHERE member_id = v_member_id AND vote_category = 'post' AND target_id = p_target_id LIMIT 1) as user_effective,
            (SELECT desired_value FROM votes WHERE member_id = v_member_id AND vote_category = 'post' AND target_id = p_target_id LIMIT 1) as user_desired
        FROM stats_posts sp
        WHERE sp.post_id = p_target_id;
        
    ELSE
        -- Unknown Category
        SELECT 0 as vote_count;
    END IF;
   
END //
DELIMITER ;
