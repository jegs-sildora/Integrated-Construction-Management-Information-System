<?php
// config/config.php

// 1. File System Path (Used for PHP includes like require_once)
define('BASE_PATH', realpath(dirname(__FILE__) . '/../'));

// 2. Web URL Path (Used for links, CSS, JS in HTML)
// Update 'http://localhost/icmis/' if your URL is different
define('BASE_URL', 'http://localhost/cmis/');

// 3. Database Credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'icmis');

// 4. Start Session Globally
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 5. Error Reporting (Useful for debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>