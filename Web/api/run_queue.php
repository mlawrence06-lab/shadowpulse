<?php
// run_queue.php
// Entry point to run the Ninja Queue Consumer.
// Can be run via Cron or Loop.

require_once __DIR__ . '/core/queue_runner.php';
// Helper to get PDO if not in run_queue scope
$pdo = sp_get_pdo();

echo "[" . date('Y-m-d H:i:s') . "] Running Ninja Queue...\n";
run_ninja_queue($pdo);
echo "[" . date('Y-m-d H:i:s') . "] Done.\n";
?>