-- Repair Stats Tables to match Live API Logic
-- Run this to sync stats_boards and stats_topics with current votes

-- 1. Sync Boards (Aggregated from Topic Votes, matching top_lists.php)
TRUNCATE TABLE stats_boards;
INSERT INTO stats_boards (board_id, vote_count, total_score)
SELECT 
    cm.board_id, 
    COUNT(*) as vote_count, 
    SUM(CAST(v.effective_value AS DECIMAL(10,4))) as total_score
FROM votes v
JOIN content_metadata cm ON v.target_id = cm.topic_id
WHERE v.vote_category = 'topic'
AND cm.board_id > 0
GROUP BY cm.board_id;

-- 2. Sync Topics
TRUNCATE TABLE stats_topics;
INSERT INTO stats_topics (topic_id, vote_count, total_score)
SELECT 
    v.target_id as topic_id, 
    COUNT(*) as vote_count, 
    SUM(CAST(v.effective_value AS DECIMAL(10,4))) as total_score
FROM votes v
WHERE v.vote_category = 'topic'
GROUP BY v.target_id;
