<?php

$dbname      = 'posting_website';
$hostname    = 'localhost';
$DB_USER     = 'root';
$DB_PASSWORD = 'root';

try {
    $dbconn = new PDO(
        "mysql:host=$hostname;dbname=$dbname;charset=utf8mb4",
        $DB_USER,
        $DB_PASSWORD
    );
    $dbconn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbconn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Could not connect to the database. Skill issue.');
}