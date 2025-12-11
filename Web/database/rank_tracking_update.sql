-- Rank Tracking Update Script
-- 1. Creates aggregation tables
-- 2. Creates metadata table
-- 3. Updates stored procedure to handle hierarchical rank updates

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- --------------------------------------------------------
-- 1. Content Metadata (The Backbone)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `content_metadata` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` bigint UNSIGNED DEFAULT NULL,
  `topic_id` bigint UNSIGNED DEFAULT NULL,
  `board_id` bigint UNSIGNED DEFAULT NULL,
  `author_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_meta_post` (`post_id`),
  UNIQUE KEY `idx_meta_topic` (`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- 2. Stats Tables
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `stats_posts` (
  `post_id` bigint UNSIGNED NOT NULL,
  `total_score` bigint DEFAULT '0',
  `vote_count` int UNSIGNED DEFAULT '0',
  `average_score` decimal(5,2) GENERATED ALWAYS AS (IF(vote_count > 0, total_score / vote_count, 0)) STORED,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `stats_topics` (
  `topic_id` bigint UNSIGNED NOT NULL,
  `total_score` bigint DEFAULT '0',
  `vote_count` int UNSIGNED DEFAULT '0',
  `average_score` decimal(5,2) GENERATED ALWAYS AS (IF(vote_count > 0, total_score / vote_count, 0)) STORED,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `stats_boards` (
  `board_id` bigint UNSIGNED NOT NULL,
  `total_score` bigint DEFAULT '0',
  `vote_count` int UNSIGNED DEFAULT '0',
  `average_score` decimal(5,2) GENERATED ALWAYS AS (IF(vote_count > 0, total_score / vote_count, 0)) STORED,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`board_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `stats_profiles` (
  `member_id` int UNSIGNED NOT NULL,
  `profile_name` varchar(255) DEFAULT NULL,
  `total_score` bigint DEFAULT '0',
  `vote_count` int UNSIGNED DEFAULT '0',
  `average_score` decimal(5,2) GENERATED ALWAYS AS (IF(vote_count > 0, total_score / vote_count, 0)) STORED,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

COMMIT;

-- --------------------------------------------------------
-- 3. Helper Procedure: internal_update_rank
-- --------------------------------------------------------
DROP PROCEDURE IF EXISTS `internal_update_rank`;
DELIMITER $$
CREATE PROCEDURE `internal_update_rank`(
    IN `p_category` VARCHAR(16),
    IN `p_target_id` BIGINT UNSIGNED,
    IN `p_delta` INT,
    IN `p_is_new_vote` BOOLEAN
)
BEGIN
    DECLARE v_topic_id BIGINT UNSIGNED;
    DECLARE v_board_id BIGINT UNSIGNED;
    DECLARE v_author_id BIGINT UNSIGNED;
    DECLARE v_inc_count INT DEFAULT 0;

    IF p_is_new_vote THEN SET v_inc_count = 1; END IF;

    IF p_category = 'post' THEN
        INSERT INTO stats_posts (post_id, total_score, vote_count) VALUES (p_target_id, p_delta, v_inc_count)
        ON DUPLICATE KEY UPDATE total_score = total_score + p_delta, vote_count = vote_count + v_inc_count;

        SELECT topic_id, author_id INTO v_topic_id, v_author_id FROM content_metadata WHERE post_id = p_target_id LIMIT 1;

        IF v_topic_id IS NOT NULL THEN
            INSERT INTO stats_topics (topic_id, total_score, vote_count) VALUES (v_topic_id, p_delta, v_inc_count)
            ON DUPLICATE KEY UPDATE total_score = total_score + p_delta, vote_count = vote_count + v_inc_count;
        END IF;

        IF v_author_id IS NOT NULL THEN
            INSERT INTO stats_profiles (member_id, total_score, vote_count) VALUES (v_author_id, p_delta, v_inc_count)
            ON DUPLICATE KEY UPDATE total_score = total_score + p_delta, vote_count = vote_count + v_inc_count;
        END IF;

    ELSEIF p_category = 'topic' THEN
        INSERT INTO stats_topics (topic_id, total_score, vote_count) VALUES (p_target_id, p_delta, v_inc_count)
        ON DUPLICATE KEY UPDATE total_score = total_score + p_delta, vote_count = vote_count + v_inc_count;

        SELECT board_id INTO v_board_id FROM content_metadata WHERE topic_id = p_target_id LIMIT 1;

        IF v_board_id IS NOT NULL THEN
            INSERT INTO stats_boards (board_id, total_score, vote_count) VALUES (v_board_id, p_delta, v_inc_count)
            ON DUPLICATE KEY UPDATE total_score = total_score + p_delta, vote_count = vote_count + v_inc_count;
        END IF;

    END IF;
END$$
DELIMITER ;

-- --------------------------------------------------------
-- 4. Main Procedure: shadowpulse_cast_vote
-- --------------------------------------------------------
DROP PROCEDURE IF EXISTS `shadowpulse_cast_vote`;
DELIMITER $$
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
        CALL internal_update_rank(v_cat, p_target_id, p_desired_value, TRUE);
    ELSE
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
