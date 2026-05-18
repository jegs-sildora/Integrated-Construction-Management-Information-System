<?php
/**
 * Reports API v1 - Reports Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../Logger.php';

$db = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $db->prepare("SELECT * FROM generated_reports WHERE report_id = ? LIMIT 1");
            $stmt->execute([$id]);
            $report = $stmt->fetch();

            echo json_encode([
                'success' => (bool)$report,
                'report' => $report ?: null
            ]);
            exit;
        }

        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $offset = ($page - 1) * $per_page;
        
        $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
        $category = $_GET['category'] ?? '';
        
        $where = [];
        $params = [];
        
        if ($project_id > 0) {
            $where[] = "project_id = ?";
            $params[] = $project_id;
        }
        if (!empty($category)) {
            $where[] = "category = ?";
            $params[] = $category;
        }
        
        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $sql = "SELECT * FROM generated_reports $whereSql ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $reports = $stmt->fetchAll();
        
        $countSql = "SELECT COUNT(*) as total FROM generated_reports $whereSql";
        $stmtCount = $db->prepare($countSql);
        $stmtCount->execute($params);
        $total = $stmtCount->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'reports' => $reports,
            'total' => $total,
            'total_pages' => ceil($total / $per_page),
            'current_page' => $page
        ]);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        
        $project_id = $input['project_id'] ?? null;
        $report_type = $input['report_type'] ?? '';
        $category = $input['category'] ?? '';
        $report_name = $input['report_name'] ?? '';
        $generated_by = $input['generated_by'] ?? 'System';
        
        if (empty($report_type) || empty($report_name)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Report type and name are required']);
            exit;
        }
        
        $sql = "INSERT INTO generated_reports (project_id, report_type, category, report_name, generated_by) 
                VALUES (?, ?, ?, ?, ?) RETURNING report_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([$project_id, $report_type, $category, $report_name, $generated_by]);
        $newId = $stmt->fetchColumn();

        Logger::create('Reports', "Saved report metadata: $report_name ($report_type)", $newId);
        
        echo json_encode(['success' => true, 'message' => 'Report metadata saved successfully']);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

