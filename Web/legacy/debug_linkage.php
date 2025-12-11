<?php
// debug_linkage.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain');

require_once 'db.php';
$pdo = sp_get_pdo();

// 1. Get the most recently active UUID from the database
// (Since we can't easily get your browser's UUID without you sending it, 
// we'll guess you are the person who last touched the database).
$sql = "SELECT member_uuid, member_id, last_seen_at FROM members ORDER BY last_seen_at DESC LIMIT 1";
$user = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("CRITICAL ERROR: The 'members' table is completely empty. No wonder stats are 0!");
}

echo "--- DEBUGGING IDENTITY LINKAGE ---\n";
echo "Most recent user found in DB:\n";
echo "UUID:      " . $user['member_uuid'] . "\n";
echo "Member ID: " . $user['member_id'] . "\n";
echo "Last Seen: " . $user['last_seen_at'] . "\n\n";

// 2. Check Member Stats for this ID
$sql = "SELECT * FROM member_stats WHERE member_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user['member_id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

echo "--- CHECKING STATS TABLE ---\n";
if ($stats) {
    echo "SUCCESS: Found a row in member_stats for ID " . $user['member_id'] . ".\n";
    echo "Page Views:    " . $stats['page_views'] . "\n";
    echo "Topic Votes:   " . $stats['topic_votes'] . "\n";
    echo "Search Count:  " . $stats['searches_made'] . "\n";
    
    if ($stats['page_views'] == 0) {
        echo "\nDIAGNOSIS: The row exists, but the values ARE actually 0 in the database.\n";
        echo "If you see '226' in phpMyAdmin, you are looking at the wrong row (wrong member_id).\n";
    } else {
        echo "\nDIAGNOSIS: The database has data! If your extension shows 0, your extension is sending a DIFFERENT UUID than the one listed above.\n";
    }
} else {
    echo "FAILURE: No row found in member_stats for Member ID " . $user['member_id'] . ".\n";
    echo "DIAGNOSIS: You have a user in 'members', but they are missing from 'member_stats'.\n";
    echo "SOLUTION: Run the INSERT/COPY command again to sync the tables.\n";
}
?>