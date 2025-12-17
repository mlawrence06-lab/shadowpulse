<?php
// run_sql_file.php
// Wrapper to execute SQL file (Authenticated)
// Usage: run_sql_file.php?file=optimize_polling.sql&pass=bxzziug_secret

require_once __DIR__ . '/../../config/db.php';

header('Content-Type: text/plain');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

$pass = $_GET['pass'] ?? '';
if ($pass !== 'bxzziug_secret') {
    http_response_code(403);
    die("Unauthorized.");
}

try {
    $fileParam = $_GET['file'] ?? '';
    // Security: Only allow basename to prevent directory traversal
    $filename = basename($fileParam);

    if (empty($filename)) {
        die("Error: No file specified.");
    }

    // Look in Web/database/ first, then current dir
    $paths = [
        __DIR__ . '/../../database/' . $filename,
        __DIR__ . '/' . $filename
    ];

    $targetFile = null;
    foreach ($paths as $p) {
        if (file_exists($p)) {
            $targetFile = $p;
            break;
        }
    }

    if (!$targetFile) {
        die("Error: File not found: $filename");
    }

    echo "Reading SQL file: $targetFile\n";
    $sql = file_get_contents($targetFile);

    // Connect
    $pdo = sp_get_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Simple Parser for Delimiters
    // Normalize newlines
    $sql = str_replace(["\r\n", "\r"], "\n", $sql);
    $lines = explode("\n", $sql);

    $buffer = '';
    $delimiter = ';';
    $count = 0;

    foreach ($lines as $line) {
        $trimLine = trim($line);
        // Skip purely empty lines or comments (simple check)
        if ($trimLine === '' || strpos($trimLine, '--') === 0)
            continue;

        // Check for delimiter change
        if (preg_match('/^DELIMITER\s+(\S+)/i', $trimLine, $matches)) {
            $delimiter = $matches[1];
            continue;
        }

        $buffer .= $line . "\n";

        // Check if statement ends with delimiter
        // We trim the buffer end to check for delimiter
        if (substr(trim($buffer), -strlen($delimiter)) === $delimiter) {
            $stmt = substr(trim($buffer), 0, -strlen($delimiter));
            if (trim($stmt) !== '') {
                try {
                    // Check if it's a SELECT/SHOW/DESCRIBE
                    if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)/i', $stmt)) {
                        $q = $pdo->query($stmt);
                        $rows = $q->fetchAll(PDO::FETCH_ASSOC);
                        echo "Result for query #$count:\n";
                        print_r($rows);
                        echo "\n";
                    } else {
                        $pdo->exec($stmt);
                        echo "Executed statement #$count (Non-SELECT)\n";
                    }
                    $count++;
                } catch (Exception $e) {
                    echo "Error executing statement #$count:\n" . substr($stmt, 0, 100) . "...\nReason: " . $e->getMessage() . "\n";
                    die("Aborted.");
                }
            }
            $buffer = '';
        }
    }

    echo "Success. Executed $count statements.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>