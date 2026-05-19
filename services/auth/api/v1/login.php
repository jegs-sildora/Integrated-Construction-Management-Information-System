<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../JwtUtils.php';
require_once __DIR__ . '/../../Logger.php';

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email and password are required']);
        exit;
    }

    $db = \Database::getConnection();
    
    // Case-insensitive search using ILIKE
    $stmt = $db->prepare("SELECT user_id, full_name, password, role FROM users WHERE email ILIKE ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid credentials', 'message' => 'User not found']);
        exit;
    }

    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid credentials', 'message' => 'Incorrect password']);
        exit;
    }

    $payload = [
        'user_id' => (int)$user['user_id'],
        'user_name' => $user['full_name'],
        'user_role' => $user['role']
    ];

    $token = JwtUtils::generate($payload);

    // Log successful login
    Logger::login('User logged in successfully via API', $user['user_id'], $user['full_name']);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => $payload
    ]);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Auth Service Error',
        'message' => $e->getMessage()
    ]);
}
