-- Database Schema Dump
-- Provided by user on 2025-12-11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table: btc_price_history
-- --------------------------------------------------------
CREATE TABLE `btc_price_history` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `symbol` varchar(10) NOT NULL,
  `candle_time` datetime NOT NULL,
  `open_price` decimal(20,8) NOT NULL,
  `high_price` decimal(20,8) NOT NULL,
  `low_price` decimal(20,8) NOT NULL,
  `close_price` decimal(20,8) NOT NULL,
  `volume` decimal(20,8) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_candle` (`symbol`,`candle_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------
-- Table: members
-- --------------------------------------------------------
CREATE TABLE `members` (
  `member_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_uuid` char(36) NOT NULL,
  `restore_ack` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_seen_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `pref_theme` varchar(10) DEFAULT 'light',
  `pref_search` varchar(20) DEFAULT 'bitlist',
  `pref_btc_source` varchar(20) DEFAULT 'binance',
  PRIMARY KEY (`member_id`),
  UNIQUE KEY `member_uuid` (`member_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------
-- Table: member_stats
-- --------------------------------------------------------
CREATE TABLE `member_stats` (
  `member_id` bigint UNSIGNED NOT NULL,
  `page_views` int UNSIGNED DEFAULT '0',
  `searches_made` int UNSIGNED DEFAULT '0',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `topic_votes` int UNSIGNED DEFAULT '0',
  `post_votes` int UNSIGNED DEFAULT '0',
  PRIMARY KEY (`member_id`),
  KEY `idx_stats_page_views` (`page_views` DESC),
  KEY `idx_stats_searches` (`searches_made` DESC),
  KEY `idx_stats_topic_votes` (`topic_votes` DESC),
  KEY `idx_stats_post_votes` (`post_votes` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------
-- Table: search_logs
-- --------------------------------------------------------
CREATE TABLE `search_logs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` bigint UNSIGNED NOT NULL,
  `search_term` varchar(255) NOT NULL,
  `engine_id` tinyint UNSIGNED DEFAULT '1',
  `searched_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_member_search` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------
-- Table: votes
-- --------------------------------------------------------
CREATE TABLE `votes` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` bigint UNSIGNED NOT NULL,
  `vote_category` enum('topic','post','profile','board') NOT NULL,
  `target_id` bigint UNSIGNED NOT NULL,
  `desired_value` tinyint UNSIGNED NOT NULL,
  `effective_value` tinyint UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_votes_member_target` (`member_id`,`vote_category`,`target_id`),
  KEY `idx_votes_target_category_created` (`vote_category`,`target_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------
-- Table: vote_logs
-- --------------------------------------------------------
CREATE TABLE `vote_logs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` bigint UNSIGNED NOT NULL,
  `vote_category` varchar(16) NOT NULL,
  `target_id` bigint UNSIGNED NOT NULL,
  `desired_value` tinyint UNSIGNED NOT NULL,
  `effective_value` tinyint UNSIGNED NOT NULL,
  `logged_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_member_log` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=DYNAMIC;

COMMIT;
