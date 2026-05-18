<?php
require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../JwtUtils.php';
require_once __DIR__ . '/../../Logger.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password are required']);
    exit;
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT user_id, full_name, password, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

$payload = [
    'user_id' => $user['user_id'],
    'user_name' => $user['full_name'],
    'user_role' => $user['role']
];

$token = JwtUtils::generate($payload);

// Log successful login
Logger::login('User logged in successfully via API', $user['user_id'], $user['full_name']);

echo json_encode([
    'message' => 'Login successful',
    'token' => $token,
    'user' => $payload
]);

