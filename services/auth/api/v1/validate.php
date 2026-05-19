<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . '/../../JwtUtils.php';

header('Content-Type: application/json');

$token = '';
$headers = apache_request_headers();
if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token required']);
    exit;
}

$payload = JwtUtils::validate($token);

if (!$payload) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired token']);
    exit;
}

echo json_encode(['valid' => true, 'user' => $payload]);
