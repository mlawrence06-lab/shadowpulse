INSERT INTO content_metadata (topic_id, board_id, author_id, author_name) 
VALUES (5568013, 1, 0, 'Unknown')
ON DUPLICATE KEY UPDATE board_id = 1;

-- Also verify insert
SELECT * FROM content_metadata WHERE topic_id = 5568013;
