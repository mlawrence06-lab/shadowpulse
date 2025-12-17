<?php
require_once __DIR__ . '/../../config/db.php';

try {
    $pdo = sp_get_pdo();
    $sqlFile = __DIR__ . '/../../database/alter_sp_context_rank.sql';

    if (!file_exists($sqlFile)) {
        die("SQL file not found: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);

    // Split by DELIMITER $$
    // This is tricky in PHP unless we use a robust parser.
    // Simple approach: standard exact replacement for this specific file.

    // The file content is:
    // DELIMITER $$
    // DROP ... $$
    // CREATE ... $$
    // DELIMITER ;

    // We can manually run the commands.
    $cmds = explode("$$", $sql);

    foreach ($cmds as $cmd) {
        $cmd = trim($cmd);
        if (empty($cmd) || stripos($cmd, 'DELIMITER') === 0)
            continue;

        try {
            $pdo->exec($cmd);
            echo "Executed command length " . strlen($cmd) . "\n";
        } catch (Exception $e) {
            echo "Error executing command: " . $e->getMessage() . "\n";
        }
    }

    echo "SP Update Complete.";

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage();
}
?>