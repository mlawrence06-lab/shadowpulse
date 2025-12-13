<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../core/metadata.php';

header('Content-Type: text/plain');
echo "Starting Metadata Repair (Phase 3: Force Debug 6311902)...\n";

$pdo = sp_get_pdo();
$targetId = 6311902;

// 1. Dump Votes
echo "--- VOTES TABLE ---\n";
$stmt = $pdo->prepare("SELECT * FROM votes WHERE target_id = ?");
$stmt->execute([$targetId]);
$votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($votes);

// 2. Dump Metadata
echo "\n--- METADATA TABLE ---\n";
$stmt = $pdo->prepare("SELECT * FROM content_metadata WHERE post_id = ?");
$stmt->execute([$targetId]);
$meta = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($meta);

// 3. Force API Fetch
echo "\n--- NINJA API ---\n";
$url = "https://api.ninjastic.space/posts/" . intval($targetId);
$data = fetch_json_via_curl($url);
// print_r($data);

$author_id = 0;
$title = "Unknown";
$author = "Unknown";

if (isset($data['result']) && $data['result'] === 'success') {
    $post = null;
    if (!empty($data['data'])) {
        $post = is_array($data['data']) && isset($data['data'][0]) ? $data['data'][0] : null;
    }

    if ($post) {
        $author_id = isset($post['author_uid']) ? (int) $post['author_uid'] : 0;
        $title = strip_tags($post['subject'] ?? '');
        $author = strip_tags($post['author'] ?? '');
        echo "API Result -> Author: $author_id ($author)\n";
    } else {
        echo "API Result -> Failed to parse post object.\n";
    }
} else {
    echo "API Result -> Failed (API Error).\n";
}

// 4. Force Update
if ($author_id > 0) {
    echo "\n--- EXECUTING FORCE UPDATE ---\n";
    // Check if exists
    if (!empty($meta)) {
        $sql = "UPDATE content_metadata SET author_id = ?, author_name = ? WHERE post_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$author_id, $author, $targetId]);
        echo "Update executed.\n";
    } else {
        $sql = "INSERT INTO content_metadata (post_id, author_id, author_name) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$targetId, $author_id, $author]);
        echo "Insert executed.\n";
    }
} else {
    echo "Skipping update (No valid API data).\n";
}

echo "Done.\n";
?>