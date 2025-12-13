<?php
require_once __DIR__ . '/../../config/db.php';
$pdo = sp_get_pdo();

// Add 'topic_name'
try {
    $pdo->exec("ALTER TABLE content_metadata ADD COLUMN topic_title VARCHAR(255) NULL AFTER topic_id");
    echo "Added topic_title\n";
} catch (Exception $e) {
    echo "topic_title likely exists: " . $e->getMessage() . "\n";
}

// Add 'author_name'
try {
    $pdo->exec("ALTER TABLE content_metadata ADD COLUMN author_name VARCHAR(100) NULL AFTER author_id");
    echo "Added author_name\n";
} catch (Exception $e) {
    echo "author_name likely exists: " . $e->getMessage() . "\n";
}
?>