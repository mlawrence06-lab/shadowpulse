<?php
// wipe_table.php?table=X
// Wipes a single table.
require __DIR__ . '/../../config/db.php';
header('Content-Type: text/plain');

$table = isset($_GET['table']) ? $_GET['table'] : '';
$allowed = ['search_logs', 'vote_logs', 'votes', 'member_stats', 'members'];

if (!in_array($table, $allowed)) {
    die("Invalid table");
}

try {
    $pdo = sp_get_pdo();
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE `$table`");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Done: $table";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>