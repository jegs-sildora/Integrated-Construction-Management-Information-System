<?php
// Prevent direct access (optional check)
if (!defined('BASE_PATH')) {
    // If BASE_PATH isn't defined, try to load config, or exit
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
    } else {
        die("Configuration file not found.");
    }
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>