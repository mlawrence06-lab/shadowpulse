-- Reset Database SQL Script
-- Deletes all member data, votes, logs, and aggregated statistics.
-- Preserves content_metadata and btc_price_history.

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Logs
TRUNCATE TABLE search_logs;
TRUNCATE TABLE vote_logs;

-- 2. Votes
TRUNCATE TABLE votes;

-- 3. Member Data
TRUNCATE TABLE member_stats;
TRUNCATE TABLE members;

-- 4. Aggregated Statistics (The missing tables)
TRUNCATE TABLE stats_topics;
TRUNCATE TABLE stats_posts;
TRUNCATE TABLE stats_profiles;
TRUNCATE TABLE stats_boards;
TRUNCATE TABLE content_metadata;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Database reset successfully' AS result;
