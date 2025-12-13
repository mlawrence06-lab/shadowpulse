<?php
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: text/plain');
echo "Deploying Stored Procedures...\n";

$pdo = sp_get_pdo();

$sql = <<<SQL
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
    
    -- Normalize category
    SET v_cat = CASE LOWER(TRIM(p_vote_category)) 
        WHEN 'topic' THEN 'topic' 
        WHEN 'post' THEN 'post' 
        WHEN 'profile' THEN 'profile' 
        WHEN 'board' THEN 'board' 
        ELSE 'topic' 
    END;

    -- 1. Get Member ID
    SELECT member_id INTO v_member_id FROM members WHERE member_uuid = p_member_uuid LIMIT 1;

    -- Return result set
    SELECT 
        -- Aggregates
        COUNT(*) as total_count,
        AVG(effective_value) as avg_val,
        
        -- User Vote (if member found)
        (SELECT effective_value FROM votes WHERE member_id = v_member_id AND vote_category = v_cat AND target_id = p_target_id LIMIT 1) as current_effective_value,
        (SELECT desired_value FROM votes WHERE member_id = v_member_id AND vote_category = v_cat AND target_id = p_target_id LIMIT 1) as current_desired_value
        
    FROM votes 
    WHERE vote_category = v_cat AND target_id = p_target_id;

END$$

DELIMITER ;
SQL;

// PDO doesn't like DELIMITER command. We must split/clean it.
// Actually, with PDO, we can't use DELIMITER. We just run the CREATE statement.
// And we must strip DELIMITER lines.

$sqlClean = "DROP PROCEDURE IF EXISTS `shadowpulse_get_vote_summary`;";
$pdo->exec($sqlClean);
echo "Dropped old procedure.\n";

$createSql = <<<SQL
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
        v_cat as debug_category,
        p_target_id as debug_target_id,
        (SELECT effective_value FROM votes WHERE member_id = v_member_id AND vote_category = v_cat AND target_id = p_target_id LIMIT 1) as current_effective_value,
        (SELECT desired_value FROM votes WHERE member_id = v_member_id AND vote_category = v_cat AND target_id = p_target_id LIMIT 1) as current_desired_value
    FROM votes 
    WHERE vote_category = v_cat AND target_id = p_target_id;

END
SQL;

try {
    $pdo->exec($createSql);
    echo "Created new procedure successfully.\n";
} catch (PDOException $e) {
    echo "Error creating procedure: " . $e->getMessage() . "\n";
}
?>