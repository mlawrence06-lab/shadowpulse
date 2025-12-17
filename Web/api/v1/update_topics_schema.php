<?php
// update_topics_schema.php
// Creates topics_info table and drops topic_title from content_metadata

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: text/plain');

try {
    $pdo = sp_get_pdo();

    echo "1. Creating table topics_info...\n";
    $sqlCreate = "
    CREATE TABLE IF NOT EXISTS topics_info (
        topic_id INT UNSIGNED NOT NULL PRIMARY KEY,
        topic_title VARCHAR(255) NULL,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sqlCreate);
    echo "   OK.\n\n";

    echo "2. Checking for topic_title in content_metadata...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM content_metadata LIKE 'topic_title'");
    if ($stmt->fetch()) {
        echo "   Column exists. Dropping topic_title...\n";
        $pdo->exec("ALTER TABLE content_metadata DROP COLUMN topic_title");
        echo "   Dropped.\n";
    } else {
        echo "   Column does not exist (already dropped).\n";
    }

    echo "\nSchema update complete.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>