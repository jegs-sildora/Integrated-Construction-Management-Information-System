<?php
require_once __DIR__ . '/../../Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$full_name = trim($input['full_name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($full_name) || empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

$db = Database::getConnection();

// Check if email exists
$stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'Email already exists']);
    exit;
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$role = 'Admin'; // Default role as per monolithic logic

$stmt = $db->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
if ($stmt->execute([$full_name, $email, $hashed_password, $role])) {
    http_response_code(201);
    echo json_encode(['message' => 'Account created successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed']);
}
