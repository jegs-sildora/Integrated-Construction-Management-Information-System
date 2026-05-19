<?php
/**
 * Reports API v1 - Workforce Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$db = \Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    $type = $_GET['type'] ?? '';
    $project_id = intval($_GET['project_id'] ?? 0);
    $month = $_GET['month'] ?? date('Y-m');

    if ($method !== 'GET') {
        throw new Exception("Method not allowed");
    }

    if (!$project_id) {
        throw new Exception("Project ID required");
    }

    $data = [];
    switch ($type) {
        case 'employee-directory':
            $data = getEmployeeDirectory($db, $project_id);
            break;
        case 'attendance-summary':
            $data = getAttendanceSummary($db, $project_id, $month);
            break;
        case 'assignment-report':
            $data = getAssignments($db, $project_id);
            break;
        case 'payroll-report':
            $data = getPayrollReport($db, $project_id, $month);
            break;
        default:
            throw new Exception("Invalid report type");
    }

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getEmployeeDirectory($db, $project_id) {
    $sql = "SELECT DISTINCT e.employee_id, e.employee_code, e.first_name, e.last_name,
                   e.email, e.phone, e.status, e.hire_date,
                   a.role
            FROM employees e
            JOIN assignments a ON e.employee_id = a.employee_id
            WHERE a.project_id = ?
            ORDER BY e.last_name, e.first_name";
    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id]);
    return ['employees' => $stmt->fetchAll()];
}

function getAttendanceSummary($db, $project_id, $month) {
    $start_date = $month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    
    $sql = "SELECT e.employee_id, e.employee_code, 
                   CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                   COUNT(CASE WHEN att.status = 'Present' THEN 1 END) as present_days,
                   COUNT(CASE WHEN att.status = 'Absent' THEN 1 END) as absent_days,
                   COUNT(CASE WHEN att.status = 'On Leave' THEN 1 END) as leave_days
            FROM employees e
            JOIN assignments a ON e.employee_id = a.employee_id
            LEFT JOIN attendance att ON e.employee_id = att.employee_id 
                AND att.project_id = ? AND att.attendance_date BETWEEN ? AND ?
            WHERE a.project_id = ? AND a.status = 'Active'
            GROUP BY e.employee_id, e.employee_code, e.first_name, e.last_name
            ORDER BY e.last_name, e.first_name";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id, $start_date, $end_date, $project_id]);
    $rows = $stmt->fetchAll();
    
    foreach ($rows as &$row) {
        $total_days = $row['present_days'] + $row['absent_days'] + $row['leave_days'];
        $row['attendance_rate'] = $total_days > 0 ? round(($row['present_days'] / $total_days) * 100, 1) : 0;
    }
    
    return ['attendance' => $rows];
}

function getAssignments($db, $project_id) {
    $sql = "SELECT a.assignment_id,
                   CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                   e.employee_code,
                   a.role,
                   a.start_date,
                   a.end_date,
                   a.status,
                   a.phase_id
            FROM assignments a
            JOIN employees e ON a.employee_id = e.employee_id
            WHERE a.project_id = ?
            ORDER BY a.status, e.last_name, e.first_name";
    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id]);
    return ['assignments' => $stmt->fetchAll()];
}

function getPayrollReport($db, $project_id, $month) {
    $start_date = $month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    
    $sql = "SELECT e.employee_id, e.employee_code,
                   CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                   COALESCE(e.daily_rate, jt.default_daily_rate, 800) as daily_rate,
                   jt.title_name as job_title,
                   COUNT(CASE WHEN att.status = 'Present' THEN 1 END) + 
                   COUNT(CASE WHEN att.status = 'Late' THEN 1 END) as days_worked
            FROM employees e
            JOIN assignments a ON e.employee_id = a.employee_id
            LEFT JOIN job_titles jt ON e.job_title_id = jt.job_title_id
            LEFT JOIN attendance att ON e.employee_id = att.employee_id 
                AND att.project_id = ? AND att.attendance_date BETWEEN ? AND ?
            WHERE a.project_id = ? AND a.status = 'Active'
            GROUP BY e.employee_id, e.employee_code, e.first_name, e.last_name, e.daily_rate, jt.default_daily_rate, jt.title_name
            ORDER BY e.last_name, e.first_name";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id, $start_date, $end_date, $project_id]);
    $rows = $stmt->fetchAll();
    
    $totals = ['gross' => 0, 'deductions' => 0, 'net' => 0];
    foreach ($rows as &$row) {
        $daily_rate = floatval($row['daily_rate']);
        $gross = $row['days_worked'] * $daily_rate;
        $deductions = $gross * 0.05; 
        $net = $gross - $deductions;
        
        $row['gross_pay'] = $gross;
        $row['deductions'] = $deductions;
        $row['net_pay'] = $net;
        
        $totals['gross'] += $gross;
        $totals['deductions'] += $deductions;
        $totals['net'] += $net;
    }
    
    return ['payroll' => $rows, 'totals' => $totals];
}
