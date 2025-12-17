-- shadowpulse_cast_vote stored procedure
-- Saved from phpMyAdmin dump provided by user




DROP PROCEDURE IF EXISTS `shadowpulse_cast_vote`;
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
  DECLARE v_new_eff tinyint UNSIGNED;

  SET v_cat = CASE LOWER(TRIM(p_vote_category)) 
    WHEN 'topic' THEN 'topic' WHEN 'post' THEN 'post' WHEN 'profile' THEN 'profile' WHEN 'board' THEN 'board' ELSE 'topic' 
  END;

  IF p_desired_value < 1 OR p_desired_value > 5 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'shadowpulse_cast_vote: desired_value must be between 1 and 5';
  END IF;

  START TRANSACTION;

    INSERT INTO votes (member_id, vote_category, target_id, desired_value, effective_value, created_at, updated_at)
      VALUES (p_member_id, v_cat, p_target_id, p_desired_value, p_desired_value, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      desired_value = VALUES(desired_value),
      effective_value = VALUES(effective_value),
      updated_at = VALUES(updated_at);

    SELECT
      IFNULL(SUM(CASE WHEN effective_value = 3 THEN 1 ELSE 0 END), 0),
      IFNULL(SUM(CASE WHEN effective_value IN (2, 4) THEN 1 ELSE 0 END), 0),
      IFNULL(SUM(CASE WHEN effective_value IN (1, 5) THEN 1 ELSE 0 END), 0) INTO v_C, v_M, v_E
    FROM votes WHERE member_id = p_member_id AND vote_category = v_cat;

    SET v_M_cap = FLOOR(v_C / 2);
    SET v_E_cap = FLOOR(v_M / 2);

  demote_extremes: WHILE v_E > v_E_cap DO
      SELECT id, effective_value INTO v_vote_id, v_eff FROM votes
      WHERE member_id = p_member_id AND vote_category = v_cat AND effective_value IN (1, 5)
      ORDER BY created_at ASC LIMIT 1;
      IF v_vote_id IS NULL THEN LEAVE demote_extremes; END IF;
      UPDATE votes SET effective_value = CASE WHEN v_eff = 1 THEN 2 ELSE 4 END, updated_at = NOW() WHERE id = v_vote_id;
      SET v_E = v_E - 1; SET v_M = v_M + 1; SET v_E_cap = FLOOR(v_M / 2);
    END WHILE demote_extremes;

  demote_moderates: WHILE v_M > v_M_cap DO
      SELECT id INTO v_vote_id FROM votes
      WHERE member_id = p_member_id AND vote_category = v_cat AND effective_value IN (2, 4)
      ORDER BY created_at ASC LIMIT 1;
      IF v_vote_id IS NULL THEN LEAVE demote_moderates; END IF;
      UPDATE votes SET effective_value = 3, updated_at = NOW() WHERE id = v_vote_id;
      SET v_M = v_M - 1; SET v_C = v_C + 1; SET v_M_cap = FLOOR(v_C / 2); SET v_E_cap = FLOOR(v_M / 2);
    END WHILE demote_moderates;

  promote_L1_to_L2: LOOP
      SET v_M_cap = FLOOR(v_C / 2);
      IF v_M + 1 > v_M_cap THEN LEAVE promote_L1_to_L2; END IF;
      SELECT id, desired_value INTO v_vote_id, v_desired FROM votes
      WHERE member_id = p_member_id AND vote_category = v_cat AND effective_value = 3 AND desired_value <> 3
      ORDER BY created_at ASC LIMIT 1;
      IF v_vote_id IS NULL THEN LEAVE promote_L1_to_L2; END IF;
      UPDATE votes SET effective_value = CASE WHEN v_desired IN (1, 2) THEN 2 WHEN v_desired IN (4, 5) THEN 4 ELSE 3 END, updated_at = NOW() WHERE id = v_vote_id;
      SET v_C = v_C - 1; SET v_M = v_M + 1;
    END LOOP promote_L1_to_L2;

  promote_L2_to_L3: LOOP
      SET v_E_cap = FLOOR(v_M / 2);
      IF v_E + 1 > v_E_cap THEN LEAVE promote_L2_to_L3; END IF;
      SELECT id, effective_value, desired_value INTO v_vote_id, v_eff, v_desired FROM votes
      WHERE member_id = p_member_id AND vote_category = v_cat AND effective_value IN (2, 4) AND desired_value IN (1, 5)
      ORDER BY created_at ASC LIMIT 1;
      IF v_vote_id IS NULL THEN LEAVE promote_L2_to_L3; END IF;
      UPDATE votes SET effective_value = CASE WHEN v_desired = 1 THEN 1 WHEN v_desired = 5 THEN 5 WHEN v_eff = 2 THEN 1 ELSE 5 END, updated_at = NOW() WHERE id = v_vote_id;
      SET v_M = v_M - 1; SET v_E = v_E + 1;
    END LOOP promote_L2_to_L3;

    SELECT effective_value INTO p_effective_value FROM votes WHERE member_id = p_member_id AND vote_category = v_cat AND target_id = p_target_id;
    INSERT INTO vote_logs (member_id, vote_category, target_id, desired_value, effective_value)
      VALUES (p_member_id, v_cat, p_target_id, p_desired_value, p_effective_value);

  COMMIT;
END;


