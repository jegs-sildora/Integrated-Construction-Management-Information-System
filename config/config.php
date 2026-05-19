<?php
// config/config.php

// 1. File System Path (Used for PHP includes like require_once)
define('BASE_PATH', realpath(dirname(__FILE__) . '/../'));

// 2. Web URL & Gateway Detection
$is_docker = (getenv('GATEWAY_HOST') || file_exists('/.dockerenv'));

/**
 * Robust HTTPS Detection for Cloud Environments (Render, Heroku, Cloudflare)
 */
function is_secure() {
    return (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ||
        ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on' ||
        ($_SERVER['HTTP_FRONT_END_HTTPS'] ?? '') === 'on' ||
        ($_SERVER['SERVER_PORT'] ?? '') == 443 ||
        (strpos($_SERVER['HTTP_HOST'] ?? '', '.onrender.com') !== false) // Auto-force HTTPS for Render domains
    );
}

$protocol = is_secure() ? "https://" : "http://";
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if ($is_docker) {
    // Docker Environment (Render or Local Docker)
    define('BASE_URL', $protocol . $http_host . '/');
    
    // Priority 1: Use GATEWAY_HOST environment variable if set (For Render)
    $gateway_host = getenv('GATEWAY_HOST') ?: 'gateway';
    
    // Ensure the host has a protocol. If not specified, use the same as the current page.
    if (strpos($gateway_host, 'http') !== 0) {
        $gateway_host = $protocol . $gateway_host;
    }
    
    define('GATEWAY_URL', rtrim($gateway_host, '/') . '/api/v1/');
} else {
    // Local Host Environment (Laragon / XAMPP)
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    $base_dir = str_replace(['/config/config.php', '\\config\\config.php'], '', $script_name);
    $base_dir = trim($base_dir, '/');
    
    define('BASE_URL', $protocol . 'localhost/'); 
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

// 5. Error Reporting
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0); 

