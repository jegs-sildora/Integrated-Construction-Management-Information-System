<?php
// config/config.php

// 1. File System Path (Used for PHP includes like require_once)
define('BASE_PATH', realpath(dirname(__FILE__) . '/../'));

// 2. Web URL & Gateway Detection
$is_docker = (getenv('GATEWAY_HOST') || file_exists('/.dockerenv'));

// Handle SSL Termination behind proxies (like Render)
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
           || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

$protocol = $is_https ? "https://" : "http://";
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if ($is_docker) {
    // Docker Environment (Render or Local Docker)
    define('BASE_URL', $protocol . $http_host . '/');
    
    // Priority 1: Use GATEWAY_HOST environment variable if set (For Render)
    // Priority 2: Default to 'gateway' (For Local Docker Compose)
    $gateway_host = getenv('GATEWAY_HOST') ?: 'gateway';
    
    // Ensure the host doesn't already have http/https prefix if we're adding it
    if (strpos($gateway_host, 'http') !== 0) {
        $gateway_host = 'http://' . $gateway_host;
    }
    
    define('GATEWAY_URL', rtrim($gateway_host, '/') . '/api/v1/');
} else {
    // Local Host Environment (Laragon / XAMPP)
    // If you access via http://localhost/icmis/, BASE_URL should reflect that.
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    $base_dir = str_replace(['/config/config.php', '\\config\\config.php'], '', $script_name);
    $base_dir = trim($base_dir, '/');
    
    // Default to /icmis/ if we can't detect it, or use the detected path
    // For Laragon standard setup:
    define('BASE_URL', $protocol . 'localhost/icmis/'); 
    define('GATEWAY_URL', 'http://localhost:8000/api/v1/');
}

// 3. Database Credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'icmis_db');

// 4. Start Session Globally
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Set default timezone
date_default_timezone_set('Asia/Manila');
ini_set('date.timezone', 'Asia/Manila');

// 5. Error Reporting (Useful for debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
