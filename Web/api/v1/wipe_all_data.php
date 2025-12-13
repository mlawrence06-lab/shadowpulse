<?php
// wipe_all_data.php
require __DIR__ . '/../../config/db.php';
header('Content-Type: text/plain');

try {
    $pdo = sp_get_pdo();

    echo "Starting Wipe...\n";

    // Disable FK checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    $tables = ['search_logs', 'vote_logs', 'votes', 'member_stats', 'members'];

    foreach ($tables as $table) {
        $pdo->exec("TRUNCATE TABLE `$table`");
        echo "Truncated $table\n";
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "Wipe Complete.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    http_response_code(500);
}
?>