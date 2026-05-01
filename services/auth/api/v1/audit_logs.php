<?php
/**
 * Audit Logs API v1 - Auth Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$db = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        
        $sql = "SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $db->query($sql);
        $logs = $stmt->fetchAll();
        
        $countSql = "SELECT COUNT(*) as total FROM audit_logs";
        $total = $db->query($countSql)->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'logs' => $logs,
            'total' => $total
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
