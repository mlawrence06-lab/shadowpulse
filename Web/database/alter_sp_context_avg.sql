DELIMITER $$

DROP PROCEDURE IF EXISTS `shadowpulse_get_page_context`$$

CREATE PROCEDURE `shadowpulse_get_page_context`(
    IN `p_member_id` BIGINT UNSIGNED, 
    IN `p_url` VARCHAR(512)
)
BEGIN
    DECLARE v_id BIGINT UNSIGNED;
    DECLARE v_type VARCHAR(16);
    DECLARE v_title VARCHAR(255);
    DECLARE v_vote TINYINT UNSIGNED;
    DECLARE v_item_rank INT UNSIGNED DEFAULT 0;
    DECLARE v_board_id INT UNSIGNED;

    -- Extract ID and Type from URL
    IF p_url LIKE '%topic=%' THEN
        SET v_type = 'topic';
        SET v_id = CONVERT(SUBSTRING_INDEX(SUBSTRING_INDEX(p_url, 'topic=', -1), '.', 1), UNSIGNED);
    ELSEIF p_url LIKE '%board=%' THEN
        SET v_type = 'board';
        SET v_id = CONVERT(SUBSTRING_INDEX(SUBSTRING_INDEX(p_url, 'board=', -1), '.', 1), UNSIGNED);
    ELSE
        SET v_type = NULL;
    END IF;

    -- Fetch Context
    SELECT 1 as result_set, v_type as type, v_id as id,
           CASE v_type
               WHEN 'topic' THEN (SELECT topic_title FROM topics_info WHERE topic_id = v_id)
               WHEN 'board' THEN (SELECT board_name FROM boards WHERE board_id = v_id LIMIT 1)
               ELSE NULL
           END AS label,
           (SELECT effective_value FROM votes WHERE member_id = p_member_id AND target_id = v_id AND vote_category = v_type) as personal_vote;

    -- Fetch Stats and Calculate Rank by Avg Score -> Oldest Vote (Tie breaker not easy in SP without date, simplified to Avg)
    -- We approximate "Oldest Vote" tied rank by just using Average.
    IF v_type = 'board' THEN
        SELECT 2 as result_set,
               sb.vote_count,
               sb.total_score,
               (sb.total_score / NULLIF(sb.vote_count, 0)) as average_score,
               (
                   SELECT COUNT(*) + 1 
                   FROM stats_boards sb2 
                   WHERE (sb2.total_score / NULLIF(sb2.vote_count, 0)) > (sb.total_score / NULLIF(sb.vote_count, 0))
               ) as item_rank
        FROM stats_boards sb
        WHERE sb.board_id = v_id;
    ELSEIF v_type = 'topic' THEN
        SELECT 2 as result_set,
               st.vote_count,
               st.total_score,
               (st.total_score / NULLIF(st.vote_count, 0)) as average_score,
                (
                   SELECT COUNT(*) + 1 
                   FROM stats_topics st2 
                   WHERE (st2.total_score / NULLIF(st2.vote_count, 0)) > (st.total_score / NULLIF(st.vote_count, 0))
               ) as item_rank
        FROM stats_topics st
        WHERE st.topic_id = v_id;
    END IF;

END$$

DELIMITER ;
