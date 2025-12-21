<?php
// db.php
// Database connection configuration.

define('DB_USER', 'bxzziugsp');
define('DB_PASS', 'Q86KElblN0ZONHJjQlR25gEtQrZROR');
define('DB_NAME', 'bxzziugsp');

function sp_get_pdo()
{
    $host = 'bxzziugsp.mysql.db';
    $db = DB_NAME;
    $user = DB_USER;
    $pass = DB_PASS;
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        throw new \Exception("Database connection failed: " . $e->getMessage());
    }
}