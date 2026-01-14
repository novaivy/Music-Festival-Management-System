<?php
// config.php - database connection
$DB_HOST = '127.0.0.1';
$DB_PORT = 3307;
$DB_USER = 'root';
$DB_PASS = '';              // default for XAMPP
$DB_NAME = 'music_festival_db';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
if ($conn->connect_error) {
    die("DB CONNECT ERROR: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");