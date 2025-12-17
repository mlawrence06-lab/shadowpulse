DELIMITER $$

DROP PROCEDURE IF EXISTS `shadowpulse_get_vote_summary`$$

CREATE PROCEDURE `shadowpulse_get_vote_summary`(
    IN `p_member_uuid` CHAR(36),
    IN `p_vote_category` VARCHAR(16),
    IN `p_target_id` BIGINT UNSIGNED
)
BEGIN
    DECLARE v_member_id BIGINT UNSIGNED;
    DECLARE v_cat ENUM('topic', 'post', 'profile', 'board');
    
    SET v_cat = CASE LOWER(TRIM(p_vote_category)) 
        WHEN 'topic' THEN 'topic' 
        WHEN 'post' THEN 'post' 
        WHEN 'profile' THEN 'profile' 
        WHEN 'board' THEN 'board' 
        ELSE 'topic' 
    END;

    SELECT member_id INTO v_member_id FROM members WHERE member_uuid = p_member_uuid LIMIT 1;

    SELECT 
        COUNT(*) as total_count,
        AVG(effective_value) as avg_val,
        (SELECT effective_value FROM votes WHERE member_id = v_member_id AND vote_category = v_cat AND target_id = p_target_id LIMIT 1) as current_effective_value,
        (SELECT desired_value FROM votes WHERE member_id = v_member_id AND vote_category = v_cat AND target_id = p_target_id LIMIT 1) as current_desired_value
    FROM votes 
    WHERE vote_category = v_cat AND target_id = p_target_id;
END$$

DELIMITER ;
