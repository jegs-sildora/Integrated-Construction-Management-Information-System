<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../Logger.php';

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $full_name = trim($input['full_name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($full_name) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'All fields are required']);
        exit;
    }

    $db = \Database::getConnection();
    if (!$db) {
        throw new Exception("Could not establish database connection");
    }

    // Check if email exists
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already exists']);
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'Admin'; 

    $stmt = $db->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?) RETURNING user_id");
    if ($stmt->execute([$full_name, $email, $hashed_password, $role])) {
        $new_user_id = $stmt->fetchColumn();
        Logger::create('Auth', "New user registered: $full_name ($email)", $new_user_id);
        
        ob_end_clean();
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Account created successfully']);
    } else {
        throw new Exception("SQL Error during registration");
    }

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Auth Service Error',
        'message' => $e->getMessage()
    ]);
}
