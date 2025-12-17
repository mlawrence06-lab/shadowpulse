CREATE TABLE IF NOT EXISTS `stats_posts` (
  `post_id` bigint(20) UNSIGNED NOT NULL,
  `vote_count` int(11) NOT NULL DEFAULT 0,
  `total_score` bigint(20) NOT NULL DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stats_profiles` (
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `vote_count` int(11) NOT NULL DEFAULT 0,
  `total_score` bigint(20) NOT NULL DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-run the SP definition just in case (optional, but harmless)
-- Included in clean_vote.sql if needed, but this file focuses on Tables.
