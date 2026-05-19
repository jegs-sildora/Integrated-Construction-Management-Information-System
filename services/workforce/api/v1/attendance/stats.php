<?php
/**
 * Attendance Stats API v1 - Workforce Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$db = \Database::getConnection();

try {
    $date = $_GET['date'] ?? date('Y-m-d');
    $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

    $stats = [
        'total' => 0,
        'present' => 0,
        'absent' => 0,
        'late' => 0
    ];

    // 1. Total Active Employees
    $sql_total = "SELECT COUNT(*) FROM employees WHERE status = 'Active'";
    if ($project_id > 0) {
        $sql_total = "SELECT COUNT(DISTINCT employee_id) FROM assignments WHERE project_id = ? AND status = 'Active'";
        $stmt = $db->prepare($sql_total);
        $stmt->execute([$project_id]);
    } else {
        $stmt = $db->query($sql_total);
    }
    $stats['total'] = intval($stmt->fetchColumn());

    // 2. Attendance Stats
    $sql_att = "SELECT status, COUNT(*) as count FROM attendance WHERE attendance_date = ?";
    $params = [$date];
    if ($project_id > 0) {
        $sql_att .= " AND project_id = ?";
        $params[] = $project_id;
    }
    $sql_att .= " GROUP BY status";
    
    $stmt = $db->prepare($sql_att);
    $stmt->execute($params);
    while ($row = $stmt->fetch()) {
        $s = strtolower($row['status']);
        if ($s === 'present') $stats['present'] = intval($row['count']);
        elseif ($s === 'absent') $stats['absent'] = intval($row['count']);
        elseif ($s === 'late') $stats['late'] = intval($row['count']);
    }

    echo json_encode(['success' => true, 'data' => $stats]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
