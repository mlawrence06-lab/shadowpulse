<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../core/metadata.php';

echo "<h1>ShadowPulse Diagnostic</h1>";

$pdo = sp_get_pdo();
$targetId = 6309543; // The post in question

// 1. Check DB
echo "<h2>1. Database State</h2>";
$stmt = $pdo->prepare("SELECT * FROM content_metadata WHERE post_id = ?");
$stmt->execute([$targetId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo "Found Metadata:<pre>" . print_r($row, true) . "</pre>";
    if ($row['topic_id'] == 0 || $row['author_id'] == 3706118) {
        echo "<h3 style='color:red'>FAILURE: Stale/Incorrect Metadata Detected. User needs to TRUNCATE.</h3>";
    } else {
        echo "<h3 style='color:green'>SUCCESS: Database has correct Metadata.</h3>";
    }
} else {
    echo "No metadata found (Clean slate).";
}

// 2. Test API Parsing
echo "<h2>2. API Parsing Test</h2>";
$url = "https://api.ninjastic.space/posts?id=" . $targetId;
echo "Fetching: $url <br/>";

$data = fetch_json_via_curl($url);
echo "Raw Data Preview: <pre>" . print_r(array_slice($data, 0, 5), true) . "</pre>";

$post = null;
if (isset($data['result']) && $data['result'] === 'success' && !empty($data['data'])) {
    $post = isset($data['data']['posts']) ? $data['data']['posts'][0] : $data['data'][0];
}

if ($post) {
    echo "Parsed Post:<pre>" . print_r($post, true) . "</pre>";
    echo "Extracted Topic ID: " . $post['topic_id'] . "<br/>";
    echo "Extracted Author ID: " . $post['author_uid'] . "<br/>";

    if ($post['topic_id'] == 577765) {
        echo "<h3 style='color:green'>LOGIC PASS: API Parsing is Correct.</h3>";
    } else {
        echo "<h3 style='color:red'>LOGIC FAIL: Parsed wrong Topic ID.</h3>";
    }
} else {
    echo "<h3 style='color:red'>API FAIL: Could not parse post data.</h3>";
}
?>