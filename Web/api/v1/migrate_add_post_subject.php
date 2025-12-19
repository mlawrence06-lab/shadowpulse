<?php
// migrate_add_post_subject.php
require_once __DIR__ . '/../../config/db.php';

try {
    $pdo = sp_get_pdo();
    echo "Checking schema...\n";

    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM content_metadata LIKE 'post_subject'");
    $col = $stmt->fetch();

    if (!$col) {
        echo "Adding post_subject column...\n";
        $pdo->exec("ALTER TABLE content_metadata ADD COLUMN post_subject VARCHAR(255) DEFAULT NULL");
        echo "Column added.\n";
    } else {
        echo "Column already exists.\n";
    }

    echo "Done.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>