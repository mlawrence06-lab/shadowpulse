-- Optimization Script for High-Frequency Polling
-- 1. Creates stats tables (if not exist)
-- 2. Backfills stats from existing votes (CRITICAL for valid data)
-- 3. Updates procedures to read/write stats

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- ==========================================
-- 1. Create Stats Tables
-- ==========================================

CREATE TABLE IF NOT EXISTS `stats_topics` (
  `topic_id` bigint UNSIGNED NOT NULL,
  `total_score` bigint DEFAULT '0',
  `vote_count` int UNSIGNED DEFAULT '0',
  `average_score` decimal(5,2) GENERATED ALWAYS AS (IF(vote_count > 0, total_score / vote_count, 0)) STORED,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stats_posts` (
  `post_id` bigint UNSIGNED NOT NULL,
  `total_score` bigint DEFAULT '0',
  `vote_count` int UNSIGNED DEFAULT '0',
  `average_score` decimal(5,2) GENERATED ALWAYS AS (IF(vote_count > 0, total_score / vote_count, 0)) STORED,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stats_boards` (
  `board_id` bigint UNSIGNED NOT NULL,
  `total_score` bigint DEFAULT '0',
  `vote_count` int UNSIGNED DEFAULT '0',
  `average_score` decimal(5,2) GENERATED ALWAYS AS (IF(vote_count > 0, total_score / vote_count, 0)) STORED,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`board_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stats_profiles` (
  `member_id` int UNSIGNED NOT NULL,
  `total_score` bigint DEFAULT '0',
  `vote_count` int UNSIGNED DEFAULT '0',
  `average_score` decimal(5,2) GENERATED ALWAYS AS (IF(vote_count > 0, total_score / vote_count, 0)) STORED,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 2. Backfill Data (Re-calculate all stats)
-- ==========================================

-- Topics
INSERT INTO stats_topics (topic_id, total_score, vote_count)
SELECT target_id, SUM(effective_value), COUNT(*)
FROM votes WHERE vote_category = 'topic'
GROUP BY target_id
ON DUPLICATE KEY UPDATE total_score = VALUES(total_score), vote_count = VALUES(vote_count);

-- Posts
INSERT INTO stats_posts (post_id, total_score, vote_count)
SELECT target_id, SUM(effective_value), COUNT(*)
FROM votes WHERE vote_category = 'post'
GROUP BY target_id
ON DUPLICATE KEY UPDATE total_score = VALUES(total_score), vote_count = VALUES(vote_count);

-- Boards
INSERT INTO stats_boards (board_id, total_score, vote_count)
SELECT target_id, SUM(effective_value), COUNT(*)
FROM votes WHERE vote_category = 'board'
GROUP BY target_id
ON DUPLICATE KEY UPDATE total_score = VALUES(total_score), vote_count = VALUES(vote_count);

-- Profiles
INSERT INTO stats_profiles (member_id, total_score, vote_count)
SELECT target_id, SUM(effective_value), COUNT(*)
FROM votes WHERE vote_category = 'profile'
GROUP BY target_id
ON DUPLICATE KEY UPDATE total_score = VALUES(total_score), vote_count = VALUES(vote_count);

COMMIT;

-- ==========================================
-- 3. Stored Procedures
-- ==========================================

DELIMITER $$

-- 3a. internal_update_rank (Maintains the stats)
DROP PROCEDURE IF EXISTS `internal_update_rank`$$
CREATE PROCEDURE `internal_update_rank`(
    IN `p_category` VARCHAR(16),
    IN `p_target_id` BIGINT UNSIGNED,
    IN `p_delta` INT,
    IN `p_is_new_vote` BOOLEAN
)
BEGIN
    DECLARE v_inc_count INT DEFAULT 0;
    IF p_is_new_vote THEN SET v_inc_count = 1; END IF;

    IF p_category = 'topic' THEN
        INSERT INTO stats_topics (topic_id, total_score, vote_count) VALUES (p_target_id, p_delta, v_inc_count)
        ON DUPLICATE KEY UPDATE total_score = total_score + p_delta, vote_count = vote_count + v_inc_count;
    
    ELSEIF p_category = 'post' THEN
        INSERT INTO stats_posts (post_id, total_score, vote_count) VALUES (p_target_id, p_delta, v_inc_count)
        ON DUPLICATE KEY UPDATE total_score = total_score + p_delta, vote_count = vote_count + v_inc_count;

    ELSEIF p_category = 'board' THEN
        INSERT INTO stats_boards (board_id, total_score, vote_count) VALUES (p_target_id, p_delta, v_inc_count)
        ON DUPLICATE KEY UPDATE total_score = total_score + p_delta, vote_count = vote_count + v_inc_count;

    ELSEIF p_category = 'profile' THEN
        INSERT INTO stats_profiles (member_id, total_score, vote_count) VALUES (p_target_id, p_delta, v_inc_count)
        ON DUPLICATE KEY UPDATE total_score = total_score + p_delta, vote_count = vote_count + v_inc_count;
    END IF;
END$$


-- 3b. shadowpulse_get_vote_summary (Optimized Read)
DROP PROCEDURE IF EXISTS `shadowpulse_get_vote_summary`$$
CREATE DEFINER=`bxzziugsp`@`%` PROCEDURE `shadowpulse_get_vote_summary`(
    IN `p_member_uuid` CHAR(36),
    IN `p_vote_category` VARCHAR(16),
    IN `p_target_id` BIGINT UNSIGNED
)
BEGIN
    DECLARE v_member_id BIGINT UNSIGNED;
    DECLARE v_cat ENUM('topic', 'post', 'profile', 'board');
    DECLARE v_total_count INT DEFAULT 0;
    DECLARE v_avg_val DECIMAL(5,2) DEFAULT 0;
    
    SET v_cat = CASE LOWER(TRIM(p_vote_category)) 
        WHEN 'topic' THEN 'topic' WHEN 'post' THEN 'post' WHEN 'profile' THEN 'profile' WHEN 'board' THEN 'board' ELSE 'topic' 
    END;

    -- 1. Get Member ID
    SELECT member_id INTO v_member_id FROM members WHERE member_uuid = p_member_uuid LIMIT 1;

    -- 2. READ STATS (O(1) Lookup)
    IF v_cat = 'topic' THEN
        SELECT vote_count, average_score INTO v_total_count, v_avg_val FROM stats_topics WHERE topic_id = p_target_id;
    ELSEIF v_cat = 'post' THEN
        SELECT vote_count, average_score INTO v_total_count, v_avg_val FROM stats_posts WHERE post_id = p_target_id;
    ELSEIF v_cat = 'board' THEN
        SELECT vote_count, average_score INTO v_total_count, v_avg_val FROM stats_boards WHERE board_id = p_target_id;
    ELSEIF v_cat = 'profile' THEN
        SELECT vote_count, average_score INTO v_total_count, v_avg_val FROM stats_profiles WHERE member_id = p_target_id;
    END IF;

    -- 3. Return Combined Result (User Vote + Stats)
    SELECT 
        v_total_count as total_count,
        v_avg_val as avg_val,
        (SELECT effective_value FROM votes WHERE member_id = v_member_id AND vote_category = v_cat AND target_id = p_target_id LIMIT 1) as current_effective_value,
        (SELECT desired_value FROM votes WHERE member_id = v_member_id AND vote_category = v_cat AND target_id = p_target_id LIMIT 1) as current_desired_value;

END$$


-- 3c. shadowpulse_cast_vote (Updates Stats via Internal Proc)
-- Same logic as before but ensures internal_update_rank is called
DROP PROCEDURE IF EXISTS `shadowpulse_cast_vote`$$
CREATE DEFINER=`bxzziugsp`@`%` PROCEDURE `shadowpulse_cast_vote`(
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
        -- New Vote: Score +Value, Count +1
        CALL internal_update_rank(v_cat, p_target_id, p_desired_value, TRUE);
    ELSE
        -- Changed Vote: Score +(New - Old), Count +0
        SET v_delta = CAST(p_desired_value AS SIGNED) - CAST(v_old_eff AS SIGNED);
        IF v_delta <> 0 THEN
            CALL internal_update_rank(v_cat, p_target_id, v_delta, FALSE);
        END IF;
    END IF;


    SELECT
      IFNULL(SUM(CASE WHEN effective_value = 3 THEN 1 ELSE 0 END), 0),
      IFNULL(SUM(CASE WHEN effective_value IN (2, 4) THEN 1 ELSE 0 END), 0),
      IFNULL(SUM(CASE WHEN effective_value IN (1, 5) THEN 1 ELSE 0 END), 0) INTO v_C, v_M, v_E
    FROM votes WHERE member_id = p_member_id AND vote_category = v_cat;

    SET v_M_cap = FLOOR(v_C / 2);
    SET v_E_cap = FLOOR(v_M / 2);

  demote_extremes: WHILE v_E > v_E_cap DO
      SELECT id, effective_value, target_id INTO v_vote_id, v_eff, v_loop_target_id FROM votes
      WHERE member_id = p_member_id AND vote_category = v_cat AND effective_value IN (1, 5)
      ORDER BY created_at ASC LIMIT 1;
      
      IF v_vote_id IS NULL THEN LEAVE demote_extremes; END IF;
      
      SET v_new_eff = CASE WHEN v_eff = 1 THEN 2 ELSE 4 END;
      
      UPDATE votes SET effective_value = v_new_eff, updated_at = NOW() WHERE id = v_vote_id;
      
      SET v_delta = CAST(v_new_eff AS SIGNED) - CAST(v_eff AS SIGNED);
      CALL internal_update_rank(v_cat, v_loop_target_id, v_delta, FALSE);

      SET v_E = v_E - 1; SET v_M = v_M + 1; SET v_E_cap = FLOOR(v_M / 2);
    END WHILE demote_extremes;


  demote_moderates: WHILE v_M > v_M_cap DO
      SELECT id, effective_value, target_id INTO v_vote_id, v_eff, v_loop_target_id FROM votes
      WHERE member_id = p_member_id AND vote_category = v_cat AND effective_value IN (2, 4)
      ORDER BY created_at ASC LIMIT 1;
      
      IF v_vote_id IS NULL THEN LEAVE demote_moderates; END IF;
      
      UPDATE votes SET effective_value = 3, updated_at = NOW() WHERE id = v_vote_id;

      SET v_delta = 3 - CAST(v_eff AS SIGNED);
      CALL internal_update_rank(v_cat, v_loop_target_id, v_delta, FALSE);

      SET v_M = v_M - 1; SET v_C = v_C + 1; SET v_M_cap = FLOOR(v_C / 2); SET v_E_cap = FLOOR(v_M / 2);
    END WHILE demote_moderates;


  promote_L1_to_L2: LOOP
      SET v_M_cap = FLOOR(v_C / 2);
      IF v_M + 1 > v_M_cap THEN LEAVE promote_L1_to_L2; END IF;
      SELECT id, desired_value, effective_value, target_id INTO v_vote_id, v_desired, v_eff, v_loop_target_id FROM votes
      WHERE member_id = p_member_id AND vote_category = v_cat AND effective_value = 3 AND desired_value <> 3
      ORDER BY created_at ASC LIMIT 1;
      
      IF v_vote_id IS NULL THEN LEAVE promote_L1_to_L2; END IF;
      
      SET v_new_eff = CASE WHEN v_desired IN (1, 2) THEN 2 WHEN v_desired IN (4, 5) THEN 4 ELSE 3 END;
      
      UPDATE votes SET effective_value = v_new_eff, updated_at = NOW() WHERE id = v_vote_id;
      
      SET v_delta = CAST(v_new_eff AS SIGNED) - CAST(v_eff AS SIGNED);
      CALL internal_update_rank(v_cat, v_loop_target_id, v_delta, FALSE);

      SET v_C = v_C - 1; SET v_M = v_M + 1;
    END LOOP promote_L1_to_L2;


  promote_L2_to_L3: LOOP
      SET v_E_cap = FLOOR(v_M / 2);
      IF v_E + 1 > v_E_cap THEN LEAVE promote_L2_to_L3; END IF;
      SELECT id, effective_value, desired_value, target_id INTO v_vote_id, v_eff, v_desired, v_loop_target_id FROM votes
      WHERE member_id = p_member_id AND vote_category = v_cat AND effective_value IN (2, 4) AND desired_value IN (1, 5)
      ORDER BY created_at ASC LIMIT 1;
      
      IF v_vote_id IS NULL THEN LEAVE promote_L2_to_L3; END IF;
      
      SET v_new_eff = CASE WHEN v_desired = 1 THEN 1 WHEN v_desired = 5 THEN 5 WHEN v_eff = 2 THEN 1 ELSE 5 END;
      
      UPDATE votes SET effective_value = v_new_eff, updated_at = NOW() WHERE id = v_vote_id;
      
      SET v_delta = CAST(v_new_eff AS SIGNED) - CAST(v_eff AS SIGNED);
      CALL internal_update_rank(v_cat, v_loop_target_id, v_delta, FALSE);

      SET v_M = v_M - 1; SET v_E = v_E + 1;
    END LOOP promote_L2_to_L3;


    SELECT effective_value INTO p_effective_value FROM votes WHERE member_id = p_member_id AND vote_category = v_cat AND target_id = p_target_id;
    
    INSERT INTO vote_logs (member_id, vote_category, target_id, desired_value, effective_value)
      VALUES (p_member_id, v_cat, p_target_id, p_desired_value, p_effective_value);

  COMMIT;
END$$

DELIMITER ;
