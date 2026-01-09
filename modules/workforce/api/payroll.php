<?php
/**
 * Payroll API - Workforce Module
 * Location: /api/payroll.php
 */
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Use the shared project context which loads the correct config and provides a connection helper
include __DIR__ . '/../project_context.php';

$conn = getWorkforceConnection();

// Helper: Standard JSON Response
function jsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

// Input Handling
$action = $_GET['action'] ?? '';
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$month = $_GET['month'] ?? date('Y-m');

try {
    switch ($action) {
        case 'get_payroll':
            getPayrollData($conn, $project_id, $month);
            break;
        default:
            jsonResponse(false, 'Invalid action');
    }
} catch (Exception $e) {
    jsonResponse(false, $e->getMessage());
}

function getPayrollData($conn, $project_id, $month) {
    if ($project_id === 0) {
        jsonResponse(true, 'No project selected', ['employees' => [], 'totals' => []]);
    }

    // 1. Define Date Range
    $start_date = $month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));

    // 2. Fetch Active Employees on this Project
    // We join assignments to filter by project, and job_titles for the daily rate.
    $sql = "SELECT 
                e.employee_id, e.employee_code, e.first_name, e.last_name, 
                a.role, 
                jt.title_name as job_title, 
                COALESCE(e.daily_rate, jt.default_daily_rate, 0) as daily_rate
            FROM workforce_assignments a
            JOIN workforce_employees e ON a.employee_id = e.employee_id
            LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
            WHERE a.project_id = ? 
            AND a.status = 'Active'
            ORDER BY e.last_name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $payroll_list = [];
    $totals = ['gross' => 0, 'deductions' => 0, 'net' => 0, 'count' => 0];

    // 3. Calculate Payroll for each employee
    // OPTIMIZATION NOTE: In a massive system, you would query attendance in one big batch (GROUP BY employee_id). 
    // For < 500 employees, this loop is acceptable and easier to read.
    
    $attStmt = $conn->prepare("SELECT 
        COUNT(CASE WHEN status IN ('Present', 'Late') THEN 1 END) as days_worked,
        COUNT(CASE WHEN status = 'Absent' THEN 1 END) as days_absent
        FROM workforce_attendance 
        WHERE employee_id = ? AND project_id = ? AND attendance_date BETWEEN ? AND ?");

    foreach ($employees as $emp) {
        // Fetch Attendance Stats
        $attStmt->bind_param("iiss", $emp['employee_id'], $project_id, $start_date, $end_date);
        $attStmt->execute();
        $stats = $attStmt->get_result()->fetch_assoc();
        
        $days_worked = intval($stats['days_worked']);
        $days_absent = intval($stats['days_absent']);
        $rate = floatval($emp['daily_rate']);

        // --- MATH CORE ---
        $gross = $days_worked * $rate;
        
        // Simple Deduction Logic (Example: 5% if gross > 5000)
        // Adjust this logic to match your real PH tax tables (SSS/PhilHealth)
        $deductions = ($gross > 5000) ? ($gross * 0.05) : 0; 
        
        $net = $gross - $deductions;
        // ----------------

        // Accumulate Totals
        $totals['gross'] += $gross;
        $totals['deductions'] += $deductions;
        $totals['net'] += $net;
        $totals['count']++;

        $payroll_list[] = [
            'employee_id' => $emp['employee_id'],
            'fullname' => $emp['first_name'] . ' ' . $emp['last_name'],
            'code' => $emp['employee_code'],
            'role' => $emp['role'] ?: $emp['job_title'],
            'days_worked' => $days_worked,
            'days_absent' => $days_absent,
            'daily_rate' => $rate,
            'gross_pay' => $gross,
            'deductions' => $deductions,
            'net_pay' => $net
        ];
    }
    
    $attStmt->close();

    jsonResponse(true, 'Data calculated', [
        'employees' => $payroll_list,
        'totals' => $totals,
        'period_label' => date('F Y', strtotime($start_date)),
        'project_id' => $project_id
    ]);
}
?>