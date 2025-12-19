<?php
// fix_schema_final.php
header('Content-Type: text/plain');
ini_set('display_errors', 1);
require __DIR__ . '/../../config/db.php';

try {
    $pdo = sp_get_pdo();
    echo "Connected.\n";

    // 1. Drop Column (Ignore if not exists)
    try {
        $pdo->exec("ALTER TABLE member_stats DROP COLUMN page_views");
        echo "Dropped column page_views from member_stats.\n";
    } catch (Exception $e) {
        echo "Drop failed (maybe didn't exist): " . $e->getMessage() . "\n";
    }

    // 2. Add Column
    $pdo->exec("ALTER TABLE member_stats ADD COLUMN page_views BIGINT UNSIGNED DEFAULT 0");
    echo "Added column page_views to member_stats.\n";

    // 3. Verify Select
    $stmt = $pdo->query("SELECT member_id, page_views FROM member_stats LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Verification Select: OK.\n";
    if ($row) {
        echo "Row Data: " . print_r($row, true) . "\n";
    } else {
        echo "No members found (Empty table).\n";
    }

} catch (PDOException $e) {
    echo "FATAL PDO: " . $e->getMessage();
} catch (Exception $e) {
    echo "FATAL: " . $e->getMessage();
}
