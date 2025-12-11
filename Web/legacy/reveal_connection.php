<?php
// reveal_connection.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain');

require_once 'db.php';
$pdo = sp_get_pdo();

// Query the server to ask "Who am I?" and "Where am I?"
$stmt = $pdo->query("SELECT DATABASE() as db_name, USER() as db_user, VERSION() as db_version");
$info = $stmt->fetch(PDO::FETCH_ASSOC);

echo "--- LIVE CONNECTION DETAILS ---\n";
echo "The file 'db.php' is currently connecting to:\n\n";
echo "DATABASE NAME: " . $info['db_name'] . "\n";
echo "LOGGED IN AS:  " . $info['db_user'] . "\n";
echo "VERSION:       " . $info['db_version'] . "\n";
echo "\n----------------------------------\n";
echo "INSTRUCTION:\n";
echo "Go to your hosting control panel / phpMyAdmin and look for the database named '" . $info['db_name'] . "'.\n";
echo "That is the ONE AND ONLY database your app is using.\n";
echo "Ignore any other databases, even if they have similar names.\n";
?>