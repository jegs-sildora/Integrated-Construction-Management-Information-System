<?php
/**
 * Grouped Attendance API v1 - Workforce Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$db = Database::getConnection();

try {
    $date = $_GET['date'] ?? date('Y-m-d');
    $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

    $sql = "SELECT g.group_id, g.group_name, 
                   CONCAT(l.first_name, ' ', l.last_name) as leader_name,
                   e.employee_id, e.first_name, e.last_name,
                   att.status, att.time_in, att.time_out, att.remarks
            FROM employee_groups g
            LEFT JOIN employees l ON g.group_leader_id = l.employee_id
            JOIN group_memberships gm ON g.group_id = gm.group_id
            JOIN employees e ON gm.employee_id = e.employee_id
            LEFT JOIN attendance att ON e.employee_id = att.employee_id AND att.attendance_date = ?
            ORDER BY g.group_name, e.last_name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$date]);
    $rows = $stmt->fetchAll();

    $groups = [];
    foreach ($rows as $row) {
        $gid = $row['group_id'];
        if (!isset($groups[$gid])) {
            $groups[$gid] = [
                'info' => [
                    'name' => $row['group_name'],
                    'leader' => $row['leader_name']
                ],
                'stats' => ['total' => 0, 'present' => 0],
                'members' => []
            ];
        }
        
        $groups[$gid]['members'][] = [
            'employee_id' => $row['employee_id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'status' => $row['status'] ?: 'Absent',
            'time_in' => $row['time_in'],
            'time_out' => $row['time_out'],
            'remarks' => $row['remarks']
        ];
        
        $groups[$gid]['stats']['total']++;
        if ($row['status'] === 'Present' || $row['status'] === 'Late') {
            $groups[$gid]['stats']['present']++;
        }
    }

    echo json_encode(['success' => true, 'data' => $groups]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
