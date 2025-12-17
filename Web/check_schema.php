<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "Starting schema check...<br>\n";

require __DIR__ . '/config/db.php';
echo "DB Included...<br>\n";

try {
    $pdo = sp_get_pdo();
    echo "PDO Connected...<br>\n";

    function desc($pdo, $table)
    {
        echo "TABLE: $table\n";
        $stmt = $pdo->query("DESCRIBE $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "{$row['Field']} - {$row['Type']}\n";
        }
        echo "\n";
    }

    desc($pdo, 'member_stats');
    desc($pdo, 'stats_topics');
    desc($pdo, 'stats_posts');
    desc($pdo, 'stats_profiles');
    desc($pdo, 'topics_info');
    desc($pdo, 'content_metadata');

} catch (Throwable $e) {
    echo "Error: " . $e->getMessage();
}
?>