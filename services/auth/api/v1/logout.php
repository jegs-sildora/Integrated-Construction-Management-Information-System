<?php
error_reporting(0);
ini_set('display_errors', 0);
/**
 * ========================= API: Logout =========================
 * Purpose: Handle logout event logging.
 * Note: In a stateless JWT architecture, logout is primarily a 
 * client-side action (discarding the token). This endpoint 
 * provides a way to record the logout event in the audit trail.
 * ============================================================================ 
 */

require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../JwtUtils.php';
require_once __DIR__ . '/../../Logger.php';

header('Content-Type: application/json');

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = null;

if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}

$payload = null;
if ($token) {
    $payload = JwtUtils::validate($token);
}

if ($payload) {
    // Log the logout event
    Logger::log('LOGOUT', 'Auth', 'User logged out successfully via API', null, $payload['user_id'], $payload['user_name']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Logout recorded successfully'
    ]);
} else {
    // If no valid token, we still return success to the client
    echo json_encode([
        'success' => true,
        'message' => 'Session terminated'
    ]);
}
