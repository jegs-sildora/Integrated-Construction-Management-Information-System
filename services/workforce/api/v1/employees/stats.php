<?php
/**
 * Dashboard Stats API v1 - Workforce Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$db = Database::getConnection();

try {
    $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
    $today = date('Y-m-d');

    $stats = [
        'total_employees' => 0,
        'active_employees' => 0,
        'on_leave' => 0,
        'attendance_rate' => 0,
        'inactive_employees' => 0
    ];

    $project_filter = ($project_id > 0) ? "AND wa.project_id = ?" : "";
    $params = ($project_id > 0) ? [$project_id] : [];

    // 1. Employee Counts
    $sql_emp = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN e.status = 'Active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN e.status = 'On Leave' THEN 1 ELSE 0 END) as on_leave,
            SUM(CASE WHEN e.status IN ('Terminated', 'Resigned') THEN 1 ELSE 0 END) as inactive
        FROM employees e
        LEFT JOIN assignments wa ON e.employee_id = wa.employee_id
        WHERE 1=1 $project_filter
    ";
    $stmt = $db->prepare($sql_emp);
    $stmt->execute($params);
    $row = $stmt->fetch();
    
    if ($row) {
        $stats['total_employees'] = intval($row['total']);
        $stats['active_employees'] = intval($row['active']);
        $stats['on_leave'] = intval($row['on_leave']);
        $stats['inactive_employees'] = intval($row['inactive']);
    }

    // 2. Attendance
    $active_count = max(1, $stats['active_employees']);
    $sql_att = "
        SELECT COUNT(DISTINCT employee_id) as present 
        FROM attendance 
        WHERE attendance_date = ? AND status = 'Present' 
        " . ($project_id > 0 ? "AND project_id = ?" : "");
    
    $params_att = [$today];
    if ($project_id > 0) $params_att[] = $project_id;
    
    $stmt = $db->prepare($sql_att);
    $stmt->execute($params_att);
    $present_count = intval($stmt->fetchColumn());
    $stats['attendance_rate'] = round(($present_count / $active_count) * 100, 1);

    echo json_encode(['success' => true, 'data' => $stats]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
