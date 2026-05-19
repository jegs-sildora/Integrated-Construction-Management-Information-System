<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

try {
    $db = \Database::getConnection();
    $new_hash = password_hash('password123', PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = 'john.doe@icmis.com'");
    $stmt->execute([$new_hash]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Admin password has been reset to password123 using the internal PHP environment.',
        'new_hash' => $new_hash
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
