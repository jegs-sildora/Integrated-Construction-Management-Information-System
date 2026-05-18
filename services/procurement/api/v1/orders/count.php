<?php
/**
 * Purchase Orders Count API v1
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../../Database.php';

$db = Database::getConnection();

try {
    $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
    $status = isset($_GET['status']) ? strtoupper($_GET['status']) : 'APPROVED';

    $where = [];
    $params = [];

    if ($project_id > 0) {
        $where[] = 'project_id = ?';
        $params[] = $project_id;
    }
    if (!empty($status)) {
        $where[] = 'status = ?';
        $params[] = $status;
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $db->prepare("SELECT COUNT(*) FROM purchase_orders $whereSql");
    $stmt->execute($params);
    $count = intval($stmt->fetchColumn());

    echo json_encode(['success' => true, 'count' => $count]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
