-- check_stats.sql
SELECT 'votes' as tbl, count(*) as cnt FROM votes
UNION ALL
SELECT 'stats_profiles', count(*) FROM stats_profiles
UNION ALL
SELECT 'stats_boards', count(*) FROM stats_boards
UNION ALL
SELECT 'stats_topics', count(*) FROM stats_topics;

SELECT * FROM votes LIMIT 5;
