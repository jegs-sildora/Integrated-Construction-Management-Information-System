<?php
require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../Logger.php';

header('Content-Type: application/json');

$db = \Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 50;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $offset = ($page - 1) * $per_page;
        
        $module = $_GET['module'] ?? '';
        $action = $_GET['action'] ?? '';
        $user = $_GET['user'] ?? '';
        
        $where = [];
        $params = [];
        
        if (!empty($module)) {
            $where[] = "module = ?";
            $params[] = $module;
        }
        if (!empty($action)) {
            $where[] = "action = ?";
            $params[] = $action;
        }
        if (!empty($user)) {
            $where[] = "user_name ILIKE ?";
            $params[] = "%$user%";
        }
        
        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $sql = "SELECT * FROM audit_logs $whereSql ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
        
        $countSql = "SELECT COUNT(*) as total FROM audit_logs $whereSql";
        $stmtCount = $db->prepare($countSql);
        $stmtCount->execute($params);
        $total = $stmtCount->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'logs' => $logs,
            'total' => $total,
            'total_pages' => ceil($total / $per_page),
            'current_page' => $page
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
