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
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 50;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $offset = ($page - 1) * $per_page;
        
        $module = $_GET['module'] ?? '';
        $action = $_GET['action'] ?? '';
        $user = $_GET['user'] ?? '';
        
        $where = [];
        $params = [];
        
        if (!empty($module)) {
            $where[] = "l.module = ?";
            $params[] = $module;
        }
        if (!empty($action)) {
            $where[] = "l.action = ?";
            $params[] = $action;
        }
        if (!empty($user)) {
            $where[] = "(l.user_name ILIKE ? OR u.email ILIKE ?)";
            $params[] = "%$user%";
            $params[] = "%$user%";
        }
        
        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $sql = "SELECT l.*, u.role 
                FROM audit_logs l 
                LEFT JOIN users u ON l.user_id = u.user_id 
                $whereSql
                ORDER BY l.created_at DESC 
                LIMIT $per_page OFFSET $offset";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
        
        $countSql = "SELECT COUNT(*) as total FROM audit_logs l LEFT JOIN users u ON l.user_id = u.user_id $whereSql";
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
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $user_id = $input['user_id'] ?? null;
        $user_name = $input['user_name'] ?? 'System';
        $action = $input['action'] ?? '';
        $module = $input['module'] ?? '';
        $details = $input['details'] ?? '';
        $record_id = $input['record_id'] ?? null;
        $ip_address = $input['ip_address'] ?? null;
        $user_agent = $input['user_agent'] ?? null;
        
        if (empty($action) || empty($module)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action and Module are required']);
            exit;
        }
        
        $sql = "INSERT INTO audit_logs (user_id, user_name, action, module, details, record_id, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id, $user_name, $action, $module, $details, $record_id, $ip_address, $user_agent]);
        
        echo json_encode(['success' => true, 'message' => 'Log saved successfully']);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
