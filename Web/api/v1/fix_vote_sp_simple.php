<?php
// fix_vote_sp_simple.php
require __DIR__ . '/../../config/db.php';
$pdo = sp_get_pdo();

echo "Running simplified update...<br>";

try {
    $pdo->exec("DROP PROCEDURE IF EXISTS shadowpulse_cast_vote");
    echo "Dropped.<br>";
} catch (Exception $e) {
    echo "Drop Error: " . $e->getMessage() . "<br>";
}

$sql = <<<SQL
CREATE PROCEDURE `shadowpulse_cast_vote`(
    IN `p_member_id` BIGINT UNSIGNED, 
    IN `p_vote_category` VARCHAR(16), 
    IN `p_target_id` BIGINT UNSIGNED, 
    IN `p_desired_value` TINYINT UNSIGNED, 
    OUT `p_effective_value` TINYINT UNSIGNED
)
BEGIN
  -- Basic voting logic only (Simplified for stability)
  DECLARE v_cat enum ('topic', 'post', 'profile', 'board');
  
  SET v_cat = CASE LOWER(TRIM(p_vote_category)) 
    WHEN 'topic' THEN 'topic' WHEN 'post' THEN 'post' WHEN 'profile' THEN 'profile' WHEN 'board' THEN 'board' ELSE 'topic' 
  END;

  INSERT INTO votes (member_id, vote_category, target_id, desired_value, effective_value, created_at, updated_at)
    VALUES (p_member_id, v_cat, p_target_id, p_desired_value, p_desired_value, NOW(), NOW())
  ON DUPLICATE KEY UPDATE
    desired_value = VALUES(desired_value),
    effective_value = VALUES(effective_value),
    updated_at = VALUES(updated_at);

  SELECT effective_value INTO p_effective_value FROM votes WHERE member_id = p_member_id AND vote_category = v_cat AND target_id = p_target_id;
  
  INSERT INTO vote_logs (member_id, vote_category, target_id, desired_value, effective_value)
    VALUES (p_member_id, v_cat, p_target_id, p_desired_value, p_effective_value);

END;
SQL;

try {
    $pdo->exec($sql);
    echo "Created simplified procedure.<br>";
} catch (Exception $e) {
    echo "Create Error: " . $e->getMessage() . "<br>";
}
?>