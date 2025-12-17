INSERT INTO stats_topics (topic_id, vote_count, total_score)
SELECT 
    target_id, 
    COUNT(*), 
    IFNULL(SUM(effective_value), 0)
FROM votes 
WHERE vote_category = 'topic' AND target_id = 5568016
GROUP BY target_id
ON DUPLICATE KEY UPDATE 
    vote_count = VALUES(vote_count), 
    total_score = VALUES(total_score);
