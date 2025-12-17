-- Fix SP (Robust Division)
DROP PROCEDURE IF EXISTS shadowpulse_get_page_context;
DELIMITER //
CREATE PROCEDURE shadowpulse_get_page_context(
    IN p_url VARCHAR(500)
)
BEGIN
   DECLARE v_topic_id INT DEFAULT NULL;
   DECLARE v_board_id INT DEFAULT NULL;
   
   -- 1. Topic ID
   IF (p_url LIKE '%topic=%') THEN
       SET v_topic_id = CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(p_url, 'topic=', -1), '.', 1) AS UNSIGNED);
   END IF;

   -- 2. Board ID
   IF (p_url LIKE '%board=%') AND (v_topic_id IS NULL) THEN
       SET v_board_id = CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(p_url, 'board=', -1), '.', 1) AS UNSIGNED);
   END IF;

   -- Result
   IF (v_topic_id IS NOT NULL) THEN
       SELECT 
           'topic' as type,
           v_topic_id as id,
           st.vote_count,
           st.total_score,
           (st.total_score / CASE WHEN st.vote_count > 0 THEN st.vote_count ELSE 1 END) as average_score,
           (
               SELECT COUNT(*) + 1
               FROM stats_topics st2
               WHERE (st2.total_score / CASE WHEN st2.vote_count > 0 THEN st2.vote_count ELSE 1 END) > 
                     (st.total_score / CASE WHEN st.vote_count > 0 THEN st.vote_count ELSE 1 END)
           ) as item_rank
       FROM stats_topics st
       WHERE st.topic_id = v_topic_id;
       
   ELSEIF (v_board_id IS NOT NULL) THEN
       SELECT 
           'board' as type,
           v_board_id as id,
           sb.vote_count,
           sb.total_score,
           (sb.total_score / CASE WHEN sb.vote_count > 0 THEN sb.vote_count ELSE 1 END) as average_score,
           (
               SELECT COUNT(*) + 1
               FROM stats_boards sb2
               WHERE (sb2.total_score / CASE WHEN sb2.vote_count > 0 THEN sb2.vote_count ELSE 1 END) > 
                     (sb.total_score / CASE WHEN sb.vote_count > 0 THEN sb.vote_count ELSE 1 END)
           ) as item_rank
       FROM stats_boards sb
       WHERE sb.board_id = v_board_id;
   ELSE
       SELECT 'unknown' as type;
   END IF;
   
END //
DELIMITER ;
