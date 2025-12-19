<?php
// fix_row_216.php
header('Content-Type: text/plain');
require __DIR__ . '/../../config/db.php';

try {
    $pdo = sp_get_pdo();
    echo "Connected.\n";
    $mid = 216;

    echo "Checking Member 216 in member_stats...\n";
    $stmt = $pdo->query("SELECT * FROM member_stats WHERE member_id = $mid");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo "Row exists:\n";
        print_r($row);
    } else {
        echo "Row missing. Inserting...\n";
        $pdo->exec("INSERT INTO member_stats (member_id) VALUES ($mid)");
        echo "Inserted Member 216.\n";
    }

    echo "Verifying Join Query for 216:\n";
    $sql1a = "SELECT 
        m.member_id,
        COALESCE(ms.topic_votes, 0) as topic_votes
    FROM members m
    LEFT JOIN member_stats ms ON m.member_id = ms.member_id
    WHERE m.member_id = $mid";
    $res = $pdo->query($sql1a)->fetch(PDO::FETCH_ASSOC);
    echo "Join Result:\n";
    print_r($res);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
