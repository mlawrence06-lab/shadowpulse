-- Recalculate Stats for Profiles and Boards
-- Fixes missing aggregation caused by votes occurring before metadata was populated.

-- 1. Recalc Profiles (Aggregated from Post Stats)
TRUNCATE TABLE stats_profiles;
INSERT INTO stats_profiles (member_id, total_score, vote_count)
SELECT 
    cm.author_id, 
    SUM(sp.total_score) as total_score, 
    SUM(sp.vote_count) as vote_count
FROM stats_posts sp
JOIN content_metadata cm ON sp.post_id = cm.post_id
WHERE cm.author_id > 0
GROUP BY cm.author_id;

-- 2. Recalc Boards (Aggregated from Topic Stats)
TRUNCATE TABLE stats_boards;
INSERT INTO stats_boards (board_id, total_score, vote_count)
SELECT 
    map.board_id, 
    SUM(st.total_score) as total_score, 
    SUM(st.vote_count) as vote_count
FROM stats_topics st
JOIN (
    -- Get unique Topic->Board mapping (any post in the topic will do)
    SELECT DISTINCT topic_id, board_id 
    FROM content_metadata 
    WHERE board_id > 0
) map ON st.topic_id = map.topic_id

-- 3. Ensure Boards Table is Populated (Static Lookup)
INSERT IGNORE INTO boards (board_id, board_name) VALUES
(1, 'Bitcoin Discussion'), (12, 'Project Development'), (4, 'Bitcoin Technical Support'), (5, 'Marketplace'), 
(6, 'Development & Technical Discussion'), (7, 'Economics'), (8, 'Trading Discussion'), (9, 'Off-topic'), 
(14, 'Mining'), (24, 'Meta'), (57, 'Speculation'), (67, 'Altcoin Discussion'), (159, 'Announcements (Altcoins)'), 
(160, 'Mining (Altcoins)'), (161, 'Marketplace (Altcoins)'), (240, 'Tokens (Altcoins)'), (129, 'Reputation');

