<?php
header('Content-Type: application/json');
require_once 'Database.php';

try {
    $pdo = Database::getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS audit_logs (
        log_id SERIAL PRIMARY KEY,
        user_id INT,
        project_id INT,
        user_name VARCHAR(100) DEFAULT 'System',
        action VARCHAR(50) NOT NULL,
        module VARCHAR(50) NOT NULL,
        details TEXT,
        record_id INT,
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";

    $pdo->exec($sql);

    echo json_encode([
        'success' => true, 
        'message' => 'Audit logs table created or already exists.',
        'service' => 'project'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
