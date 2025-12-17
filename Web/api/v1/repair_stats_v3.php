<?php
// repair_stats_v3.php
// FORCE Repair of Stats Tables (With Schema Creation)
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: text/plain');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

try {
    $pdo = sp_get_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Starting Repair (v3 - Cache Busted)...\n";

    // 0. Ensure Tables Exist
    echo "Creating Tables if missing...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `boards` (
        `board_id` int(11) NOT NULL,
        `board_name` varchar(255) NOT NULL,
        PRIMARY KEY (`board_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `stats_boards` (
        `board_id` int(11) NOT NULL,
        `vote_count` int(11) DEFAULT 0,
        `total_score` decimal(10,4) DEFAULT 0.0000,
        PRIMARY KEY (`board_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `stats_topics` (
        `topic_id` int(11) NOT NULL,
        `vote_count` int(11) DEFAULT 0,
        `total_score` decimal(10,4) DEFAULT 0.0000,
        PRIMARY KEY (`topic_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 1. Populate Boards (Insert Ignore)
    echo "Populating Boards...\n";
    // Using explicit board ID 24 (Meta) and 12 (Proj Dev)
    $boardsSql = "INSERT IGNORE INTO boards (board_id, board_name) VALUES
    (1, 'Bitcoin Discussion'), (12, 'Project Development'), (4, 'Bitcoin Technical Support'), (5, 'Marketplace'), 
    (6, 'Development & Technical Discussion'), (7, 'Economics'), (8, 'Trading Discussion'), (9, 'Off-topic'), 
    (14, 'Mining'), (24, 'Meta'), (57, 'Speculation'), (67, 'Altcoin Discussion'), (159, 'Announcements (Altcoins)'), 
    (160, 'Mining (Altcoins)'), (161, 'Marketplace (Altcoins)'), (240, 'Tokens (Altcoins)'), (129, 'Reputation')";
    $pdo->exec($boardsSql);

    // 2. Stats Boards
    echo "Truncating stats_boards...\n";
    $pdo->exec("DELETE FROM stats_boards"); // DELETE is safer than TRUNCATE for locks sometimes, but slower. Using DELETE this time.

    echo "Inserting stats_boards...\n";
    $sql = "INSERT INTO stats_boards (board_id, vote_count, total_score)
            SELECT 
                cm.board_id, 
                COUNT(*) as vote_count, 
                SUM(CAST(v.effective_value AS DECIMAL(10,4))) as total_score
            FROM votes v
            JOIN content_metadata cm ON v.target_id = cm.topic_id
            WHERE v.vote_category = 'topic'
            AND cm.board_id > 0
            GROUP BY cm.board_id";
    $pdo->exec($sql);

    // 3. Stats Topics
    echo "Truncating stats_topics...\n";
    $pdo->exec("DELETE FROM stats_topics");

    echo "Inserting stats_topics...\n";
    $sql2 = "INSERT INTO stats_topics (topic_id, vote_count, total_score)
             SELECT 
                 v.target_id as topic_id, 
                 COUNT(*) as vote_count, 
                 SUM(CAST(v.effective_value AS DECIMAL(10,4))) as total_score
             FROM votes v
             WHERE v.vote_category = 'topic'
             GROUP BY v.target_id";
    $pdo->exec($sql2);

    echo "Repair Complete. Please Refresh Extension.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    http_response_code(500);
}
?>