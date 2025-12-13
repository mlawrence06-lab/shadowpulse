-- Wipe Messages SQL Script
-- Deletes all data related to members while preserving content_metadata and system history.

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Wipe User Activity Logs
TRUNCATE TABLE search_logs;
TRUNCATE TABLE vote_logs;

-- 2. Wipe Voting Data
TRUNCATE TABLE votes;

-- 3. Wipe Member Statistics
TRUNCATE TABLE member_stats;

-- 4. Wipe Members (Identity)
TRUNCATE TABLE members;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Member data wiped successfully.' AS result;
