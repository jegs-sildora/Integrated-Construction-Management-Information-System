<?php
// logout.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/Logger.php';

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log logout before destroying session
Logger::logout();

// 1. Unset all session variables
$_SESSION = array();

// 2. Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finally, destroy the session
session_destroy();

// 4. Redirect to login page (index.php)
header("Location: " . BASE_URL . "index.php?success=" . urlencode("Logged out successfully"));
exit();
?>