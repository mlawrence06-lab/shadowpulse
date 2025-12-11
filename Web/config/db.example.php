<?php
// db.example.php - Template for database credentials
// Rename to db.php and fill in your details.

function sp_get_pdo()
{
    $host = 'localhost';
    $db = 'your_database_name';
    $user = 'your_username';
    $pass = 'your_password';
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
        // In production, log this to a file instead of echoing
        // throw new \PDOException($e->getMessage(), (int)$e->getCode());
        die("Database connection failed.");
    }
}
