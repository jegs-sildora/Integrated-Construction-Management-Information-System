<?php
/**
 * Reports API - Workforce Module
 * * Provides data for generating PDF reports and logs generation history.
 */
session_start(); // Ensure session is active for capturing 'generated_by'
header('Content-Type: application/json');

include __DIR__ . '/../project_context.php';

$conn = getWorkforceConnection();
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'generate':
            generateReportData($conn);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();

function generateReportData($conn) {
    $type = $_REQUEST['type'] ?? '';
    $project_id = $_REQUEST['project_id'] ?? 0;
    $month = $_REQUEST['month'] ?? date('Y-m');
    
    if (!$project_id) {
        echo json_encode(['success' => false, 'message' => 'Project ID required']);
        return;
    }
    
    $data = [];
    
    switch ($type) {
        case 'employee-directory':
            $data = getEmployeeDirectoryData($conn, $project_id);
            break;
        case 'attendance-summary':
            $data = getAttendanceSummaryData($conn, $project_id, $month);
            break;
        case 'assignment-report':
            $data = getAssignmentReportData($conn, $project_id);
            break;
        case 'payroll-report':
            $data = getPayrollReportData($conn, $project_id, $month);
            break;
        case 'workforce-analytics':
            $data = getWorkforceAnalyticsData($conn, $project_id);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid report type']);
            return;
    }
    
    // Log the successful generation to the database
    logReportGeneration($conn, $project_id, $type, $month);

    echo json_encode(['success' => true, 'data' => $data]);
}

// --- Logging Function ---

function logReportGeneration($conn, $project_id, $type, $month) {
    // Map types to readable names
    $reportNames = [
        'employee-directory' => 'Employee Directory',
        'attendance-summary' => 'Attendance Summary (' . date('M Y', strtotime($month)) . ')',
        'assignment-report' => 'Assignment Report',
        'payroll-report' => 'Payroll Report (' . date('M Y', strtotime($month)) . ')',
        'workforce-analytics' => 'Workforce Analytics'
    ];

    $reportName = $reportNames[$type] ?? 'Workforce Report';
    $generatedBy = $_SESSION['user_name'] ?? 'System'; // Adjust based on your session variable

    // Check if table exists to avoid errors during development
    $checkTable = $conn->query("SHOW TABLES LIKE 'workforce_generated_reports'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $sql = "INSERT INTO workforce_generated_reports (project_id, report_type, report_name, generated_by, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("isss", $project_id, $type, $reportName, $generatedBy);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// --- Data Fetching Functions ---

function getEmployeeDirectoryData($conn, $project_id) {
    $sql = "SELECT DISTINCT e.employee_id, e.employee_code, e.first_name, e.last_name,
                   e.email, e.phone, e.status, e.hire_date,
                   a.role
            FROM workforce_employees e
            JOIN workforce_assignments a ON e.employee_id = a.employee_id
            WHERE a.project_id = ?
            ORDER BY e.last_name, e.first_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    $stmt->close();
    
    return ['employees' => $employees];
}

function getAttendanceSummaryData($conn, $project_id, $month) {
    $start_date = $month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    
    $sql = "SELECT e.employee_id, e.employee_code, 
                   CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                   COUNT(CASE WHEN att.status = 'Present' THEN 1 END) as present_days,
                   COUNT(CASE WHEN att.status = 'Absent' THEN 1 END) as absent_days,
                   COUNT(CASE WHEN att.status = 'On Leave' THEN 1 END) as leave_days,
                   COALESCE(SUM(TIMESTAMPDIFF(HOUR, att.time_in, att.time_out)), 0) as total_hours
            FROM workforce_employees e
            JOIN workforce_assignments a ON e.employee_id = a.employee_id
            LEFT JOIN workforce_attendance att ON e.employee_id = att.employee_id 
                AND att.project_id = ? AND att.attendance_date BETWEEN ? AND ?
            WHERE a.project_id = ? AND a.status = 'Active'
            GROUP BY e.employee_id, e.employee_code, e.first_name, e.last_name
            ORDER BY e.last_name, e.first_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $project_id, $start_date, $end_date, $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $attendance = [];
    while ($row = $result->fetch_assoc()) {
        $total_days = $row['present_days'] + $row['absent_days'] + $row['leave_days'];
        $row['attendance_rate'] = $total_days > 0 ? round(($row['present_days'] / $total_days) * 100, 1) : 0;
        $attendance[] = $row;
    }
    $stmt->close();
    
    return ['attendance' => $attendance];
}

function getAssignmentReportData($conn, $project_id) {
    $sql = "SELECT a.assignment_id,
                   CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                   e.employee_code,
                   p.project_name,
                   COALESCE(ph.phase_name, '-') as phase_name,
                   a.role,
                   a.start_date,
                   a.end_date,
                   a.status
            FROM workforce_assignments a
            JOIN workforce_employees e ON a.employee_id = e.employee_id
            JOIN icmis_projects p ON a.project_id = p.project_id
            LEFT JOIN icmis_project_phases ph ON a.phase_id = ph.phase_id
            WHERE a.project_id = ?
            ORDER BY a.status, e.last_name, e.first_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    $stmt->close();
    
    return ['assignments' => $assignments];
}

function getPayrollReportData($conn, $project_id, $month) {
    $start_date = $month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    
    $sql = "SELECT e.employee_id, e.employee_code,
                   CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                   COALESCE(jt.default_daily_rate, 800) as daily_rate,
                   jt.title_name as job_title,
                   COUNT(CASE WHEN att.status = 'Present' THEN 1 END) + 
                   COUNT(CASE WHEN att.status = 'Late' THEN 1 END) as days_worked
            FROM workforce_employees e
            JOIN workforce_assignments a ON e.employee_id = a.employee_id
            LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
            LEFT JOIN workforce_attendance att ON e.employee_id = att.employee_id 
                AND att.project_id = ? AND att.attendance_date BETWEEN ? AND ?
            WHERE a.project_id = ? AND a.status = 'Active'
            GROUP BY e.employee_id, e.employee_code, e.first_name, e.last_name, jt.default_daily_rate, jt.title_name
            ORDER BY e.last_name, e.first_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $project_id, $start_date, $end_date, $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $payroll = [];
    $totals = ['gross' => 0, 'deductions' => 0, 'net' => 0];
    
    while ($row = $result->fetch_assoc()) {
        $daily_rate = floatval($row['daily_rate']);
        $gross = $row['days_worked'] * $daily_rate;
        $deductions = $gross * 0.05; // 5% deductions
        $net = $gross - $deductions;
        
        $row['gross_pay'] = $gross;
        $row['deductions'] = $deductions;
        $row['net_pay'] = $net;
        
        $totals['gross'] += $gross;
        $totals['deductions'] += $deductions;
        $totals['net'] += $net;
        
        $payroll[] = $row;
    }
    $stmt->close();
    
    return ['payroll' => $payroll, 'totals' => $totals];
}

function getWorkforceAnalyticsData($conn, $project_id) {
    $stats = [
        'total_employees' => 0,
        'active_employees' => 0,
        'total_assignments' => 0,
        'avg_attendance' => 0
    ];
    
    // Total employees
    $sql = "SELECT COUNT(DISTINCT employee_id) as count FROM workforce_assignments WHERE project_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_employees'] = $result->fetch_assoc()['count'] ?? 0;
    $stmt->close();
    
    // Active employees
    $sql = "SELECT COUNT(DISTINCT employee_id) as count FROM workforce_assignments WHERE project_id = ? AND status = 'Active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['active_employees'] = $result->fetch_assoc()['count'] ?? 0;
    $stmt->close();
    
    // Total assignments
    $sql = "SELECT COUNT(*) as count FROM workforce_assignments WHERE project_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_assignments'] = $result->fetch_assoc()['count'] ?? 0;
    $stmt->close();
    
    // Avg Attendance
    $sql = "SELECT ROUND(AVG(CASE WHEN att.status = 'Present' THEN 100 ELSE 0 END), 1) as avg_rate
            FROM workforce_attendance att
            WHERE att.project_id = ? 
            AND att.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['avg_attendance'] = $result->fetch_assoc()['avg_rate'] ?? 0;
    $stmt->close();
    
    // Status breakdown
    $status_breakdown = [];
    $sql = "SELECT status, COUNT(*) as count FROM workforce_assignments WHERE project_id = ? GROUP BY status";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $status_breakdown[$row['status']] = $row['count'];
    }
    $stmt->close();
    
    return [
        'stats' => $stats,
        'status_breakdown' => $status_breakdown
    ];
}
?>