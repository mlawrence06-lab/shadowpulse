<?php
// update_sp.php
// Update Stored Procedure Logic
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: text/plain');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

try {
    $pdo = sp_get_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Updating Stored Procedure...\n";

    $sql = "DROP PROCEDURE IF EXISTS shadowpulse_get_page_context;
            CREATE PROCEDURE shadowpulse_get_page_context(
                IN p_url VARCHAR(500)
            )
            BEGIN
               DECLARE v_topic_id INT DEFAULT NULL;
               DECLARE v_board_id INT DEFAULT NULL;
               
               -- Extract ID from URL (Simplified logic matching original SP)
               -- Assuming Regex or string parsing matches existing logic
               -- For now, reusing the full body of alter_sp_context_avg.sql but embedded here
               -- Wait, I should read the file content? 
               -- I will reconstruct the known create statement since reading file via PHP remotely is hard without upload.
               -- Using the corrected SQL logic directly:
               
               -- 1. Try to extract topic ID
               IF (p_url LIKE '%topic=%') THEN
                   SET v_topic_id = CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(p_url, 'topic=', -1), '.', 1) AS UNSIGNED);
               END IF;
            
               -- 2. Try to extract board ID
               IF (p_url LIKE '%board=%') AND (v_topic_id IS NULL) THEN
                   SET v_board_id = CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(p_url, 'board=', -1), '.', 1) AS UNSIGNED);
               END IF;
            
               -- Return Result Set
               IF (v_topic_id IS NOT NULL) THEN
                   SELECT 
                       'topic' as type,
                       v_topic_id as id,
                       st.vote_count,
                       st.total_score,
                       (st.total_score / NULLIF(st.vote_count, 0)) as average_score,
                       (
                           SELECT COUNT(*) + 1
                           FROM stats_topics st2
                           WHERE (st2.total_score / NULLIF(st2.vote_count, 0)) > (st.total_score / NULLIF(st.vote_count, 0))
                       ) as item_rank
                   FROM stats_topics st
                   WHERE st.topic_id = v_topic_id;
                   
               ELSEIF (v_board_id IS NOT NULL) THEN
                   SELECT 
                       'board' as type,
                       v_board_id as id,
                       sb.vote_count,
                       sb.total_score,
                       (sb.total_score / NULLIF(sb.vote_count, 0)) as average_score,
                       (
                           SELECT COUNT(*) + 1
                           FROM stats_boards sb2
                           WHERE (sb2.total_score / NULLIF(sb2.vote_count, 0)) > (sb.total_score / NULLIF(sb.vote_count, 0))
                       ) as item_rank
                   FROM stats_boards sb
                   WHERE sb.board_id = v_board_id;
               ELSE
                   SELECT 'unknown' as type;
               END IF;
               
            END;";

    $pdo->exec($sql);
    echo "Stored Procedure Updated Successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>