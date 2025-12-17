<?php
require_once __DIR__ . '/../../config/db.php';
$pdo = sp_get_pdo();

try {
    $sql = "CREATE TABLE IF NOT EXISTS ninja_queue (
        topic_id INT UNSIGNED NOT NULL PRIMARY KEY,
        last_post_id INT UNSIGNED DEFAULT 0,
        status ENUM('pending', 'complete') DEFAULT 'pending',
        last_run TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Table 'ninja_queue' created or already exists.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>