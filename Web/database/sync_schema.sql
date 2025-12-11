-- Tracking API sync state for topics
CREATE TABLE IF NOT EXISTS `topic_sync_state` (
  `topic_id` bigint UNSIGNED NOT NULL,
  `total_replies_api` int UNSIGNED DEFAULT '0',
  `last_sync_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `widest_gap_low` bigint UNSIGNED DEFAULT '0',
  PRIMARY KEY (`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
