<?php
/**
 * Procurement KPI API v1
 * Returns counts of purchase orders by status for a project.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$db = Database::getConnection();

try {
    $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

    $where = '';
    $params = [];
    if ($project_id > 0) {
        $where = 'WHERE project_id = ?';
        $params[] = $project_id;
    }

    $stmtTotal = $db->prepare("SELECT COUNT(*) FROM purchase_orders $where");
    $stmtTotal->execute($params);
    $total = intval($stmtTotal->fetchColumn());

    $stmtPending = $db->prepare("SELECT COUNT(*) FROM purchase_orders $where" . ($where ? " AND status = 'PENDING'" : " WHERE status = 'PENDING'"));
    $stmtPending->execute($params);
    $pending = intval($stmtPending->fetchColumn());

    $stmtApproved = $db->prepare("SELECT COUNT(*) FROM purchase_orders $where" . ($where ? " AND status = 'APPROVED'" : " WHERE status = 'APPROVED'"));
    $stmtApproved->execute($params);
    $approved = intval($stmtApproved->fetchColumn());

    $stmtCompleted = $db->prepare("SELECT COUNT(*) FROM purchase_orders $where" . ($where ? " AND status = 'COMPLETED'" : " WHERE status = 'COMPLETED'"));
    $stmtCompleted->execute($params);
    $completed = intval($stmtCompleted->fetchColumn());

    echo json_encode([
        'success' => true,
        'total' => $total,
        'pending' => $pending,
        'approved' => $approved,
        'completed' => $completed
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
