<?php
/**
 * Payroll API Engine - Workforce Module
 * Location: /workforce/api/payroll.php
 * Logic: Handles Calculation, Locking, and Historical Reporting
 */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET");

// Database / context include strategy (module-relative)
if (file_exists(__DIR__ . '/../project_context.php')) {
    include_once __DIR__ . '/../project_context.php';
}
if (file_exists(__DIR__ . '/../../../config/database.php')) {
    include_once __DIR__ . '/../../../config/database.php';
}
require_once __DIR__ . '/../../../core/Logger.php';

// Establish Connection
if (function_exists('getWorkforceConnection')) {
    $conn = getWorkforceConnection();
} elseif (isset($conn) && $conn instanceof mysqli) {
    // Connection exists
} else {
    if (file_exists(__DIR__ . '/../../../config/config.php')) include_once __DIR__ . '/../../../config/config.php';
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
}

if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// PH Contribution Config (2025 Standard)
const PH_SSS_RATE = 0.045; 
const PH_SSS_MAX = 1350;   
const PH_PHILHEALTH_RATE = 0.025; 
const PH_PAGIBIG_RATE = 0.02; 
const PH_PAGIBIG_MAX = 200;

try {
    $action = $_REQUEST['action'] ?? '';
    $project_id = isset($_REQUEST['project_id']) ? intval($_REQUEST['project_id']) : 0;
    
    if ($project_id === 0) throw new Exception('Project ID is required.');

    switch ($action) {
        case 'get_payroll':
            $month = $_GET['month'] ?? date('Y-m');
            $period = intval($_GET['period'] ?? 1);
            getPayrollData($conn, $project_id, $month, $period);
            break;

        case 'lock_payroll':
            $month = $_POST['month'] ?? date('Y-m');
            $period = intval($_POST['period'] ?? 1);
            lockPayrollPeriod($conn, $project_id, $month, $period);
            break;
            
        case 'get_history':
            getPayrollHistory($conn, $project_id);
            break;

        default:
            throw new Exception('Invalid Action Request');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

/**
 * ACTION: GET HISTORY
 * Fetches closed periods and aggregates total costs specific to the project.
 */
function getPayrollHistory($conn, $project_id) {
    // We join workforce_payroll to workforce_assignments to ensure we only sum up 
    // costs for employees assigned to THIS project during that period.
    $sql = "SELECT 
                p.period_id, 
                p.start_date, 
                p.end_date, 
                p.pay_date, 
                p.status,
                COUNT(wp.payroll_id) as employee_count,
                SUM(wp.gross_pay) as total_gross,
                SUM(wp.net_pay) as total_net
            FROM workforce_payroll_periods p
            JOIN workforce_payroll wp ON p.period_id = wp.period_id
            JOIN workforce_assignments wa ON wp.employee_id = wa.employee_id
            WHERE p.status = 'Closed' 
            AND wa.project_id = ?
            GROUP BY p.period_id
            ORDER BY p.end_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $start = date('M j', strtotime($row['start_date']));
        $end = date('j, Y', strtotime($row['end_date']));
        
        $history[] = [
            'period_id' => $row['period_id'],
            'label' => "$start - $end",
            'pay_date' => date('M j, Y', strtotime($row['pay_date'])),
            'employee_count' => $row['employee_count'],
            'total_gross' => floatval($row['total_gross']),
            'total_net' => floatval($row['total_net']),
            'status' => $row['status']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $history]);
}

/**
 * ACTION: GET PAYROLL DATA (Draft/Locked)
 */
function getPayrollData($conn, $project_id, $month, $period) {
    $dates = getPeriodDates($month, $period);
    $start_date = $dates['start'];
    $end_date = $dates['end'];
    $period_label = $dates['label'];

    // Check Lock Status
    $is_locked = false;
    $period_id = 0;
    
    // Check if table exists to prevent errors on fresh installs
    $tblRes = $conn->query("SHOW TABLES LIKE 'workforce_payroll_periods'");
    if ($tblRes && $tblRes->num_rows > 0) {
        $checkLock = $conn->prepare("SELECT period_id, status FROM workforce_payroll_periods 
                                     WHERE start_date = ? AND end_date = ? LIMIT 1");
        $checkLock->bind_param("ss", $start_date, $end_date);
        $checkLock->execute();
        $lockRes = $checkLock->get_result();
        if ($row = $lockRes->fetch_assoc()) {
            $is_locked = ($row['status'] === 'Closed');
            $period_id = $row['period_id'];
        }
        $checkLock->close();
    }

    // Fetch Employees
    $sql = "SELECT 
                e.employee_id, e.employee_code, CONCAT(e.first_name, ' ', e.last_name) as fullname,
                e.payment_type, e.monthly_salary,
                COALESCE(e.daily_rate, jt.default_daily_rate, 0) as daily_rate,
                jt.title_name as role
            FROM workforce_assignments a
            JOIN workforce_employees e ON a.employee_id = e.employee_id
            LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
            WHERE a.project_id = ? AND a.status = 'Active'
            ORDER BY e.last_name ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $payroll_data = [];
    $issues = [];
    $totals = ['gross' => 0, 'deductions' => 0, 'net' => 0, 'count' => 0];

    foreach ($employees as $emp) {
        $emp_id = $emp['employee_id'];
        $record = initializeRecord($emp);

        if ($is_locked && $period_id > 0) {
            // MODE A: SAVED DATA
            $savedQ = $conn->prepare("SELECT hours_worked, gross_pay, net_pay FROM workforce_payroll WHERE period_id = ? AND employee_id = ? LIMIT 1");
            $savedQ->bind_param("ii", $period_id, $emp_id);
            $savedQ->execute();
            $saved = $savedQ->get_result()->fetch_assoc();
            
            if ($saved) {
                $record['hours_worked'] = floatval($saved['hours_worked']);
                $record['gross_pay'] = floatval($saved['gross_pay']);
                $record['net_pay'] = floatval($saved['net_pay']);
                $record['days_worked'] = round($record['hours_worked'] / 8, 2);
                $record['deductions'] = $record['gross_pay'] - $record['net_pay'];
                
                // Estimate deductions for display
                $basis = ($emp['payment_type'] === 'Monthly') ? floatval($emp['monthly_salary']) : ($record['gross_pay'] * 2);
                $deds = calculateGovtDeductions($basis);
                $record['sss_deduction'] = $deds['sss'];
                $record['philhealth_deduction'] = $deds['ph'];
                $record['pagibig_deduction'] = $deds['pi'];
                
                $record['basic_pay'] = $record['gross_pay']; // Assume flat for locked if no breakdown stored
            }
            $savedQ->close();
        } else {
            // MODE B: LIVE CALCULATION
            $attQ = $conn->prepare("SELECT time_in, time_out, status FROM workforce_attendance 
                                    WHERE employee_id = ? AND project_id = ? 
                                    AND attendance_date BETWEEN ? AND ?");
            $attQ->bind_param("iiss", $emp_id, $project_id, $start_date, $end_date);
            $attQ->execute();
            $logs = $attQ->get_result();
            
            $reg_sec = 0; $ot_sec = 0; $days_count = 0;

            while ($log = $logs->fetch_assoc()) {
                if ($log['status'] === 'Absent') continue;
                if ($log['status'] === 'Present' && empty($log['time_out'])) {
                    $issues[] = ['emp_id' => $emp_id, 'msg' => 'Missing Time Out'];
                }

                $sec = 0;
                if (!empty($log['time_in']) && !empty($log['time_out'])) {
                    $sec = strtotime($log['time_out']) - strtotime($log['time_in']);
                    if ($sec > 18000) $sec -= 3600;
                    if ($sec < 0) $sec = 0;
                } elseif ($log['status'] === 'Present') {
                    $sec = 28800; 
                }

                $reg = min(28800, $sec);
                $ot = max(0, $sec - 28800);
                
                $reg_sec += $reg;
                $ot_sec += $ot;
                
                if($reg > 0) $days_count += ($reg/28800); 
            }
            $attQ->close();

            $reg_hrs = round($reg_sec / 3600, 2);
            $ot_hrs = round($ot_sec / 3600, 2);
            
            // Financials
            if ($emp['payment_type'] === 'Monthly') {
                $hourly = ($emp['monthly_salary'] / 26) / 8;
                $basic_pay = $emp['monthly_salary'] / 2;
            } else {
                $hourly = $emp['daily_rate'] / 8;
                $basic_pay = $reg_hrs * $hourly;
            }

            $ot_pay = $ot_hrs * $hourly * 1.25;
            $gross_pay = $basic_pay + $ot_pay;

            $basis = ($emp['payment_type'] === 'Monthly') ? $emp['monthly_salary'] : ($gross_pay * 2);
            $deds = calculateGovtDeductions($basis);
            $total_ded = $deds['sss'] + $deds['ph'] + $deds['pi'];
            $net_pay = $gross_pay - $total_ded;

            $record['days_worked'] = round($days_count, 2);
            $record['hours_worked'] = $reg_hrs;
            $record['ot_hours'] = $ot_hrs;
            $record['basic_pay'] = round($basic_pay, 2);
            $record['ot_pay'] = round($ot_pay, 2);
            $record['gross_pay'] = round($gross_pay, 2);
            $record['deductions'] = round($total_ded, 2);
            $record['net_pay'] = round($net_pay, 2);
            $record['sss_deduction'] = $deds['sss'];
            $record['philhealth_deduction'] = $deds['ph'];
            $record['pagibig_deduction'] = $deds['pi'];
        }

        $totals['gross'] += $record['gross_pay'];
        $totals['deductions'] += $record['deductions'];
        $totals['net'] += $record['net_pay'];
        $totals['count']++;

        $payroll_data[] = $record;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'employees' => $payroll_data,
            'totals' => $totals,
            'issues' => $issues,
            'meta' => [
                'is_locked' => $is_locked,
                'period_label' => $period_label,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]
        ]
    ]);
}

/**
 * ACTION: LOCK PAYROLL
 */
function lockPayrollPeriod($conn, $project_id, $month, $period) {
    $dates = getPeriodDates($month, $period);
    $start = $dates['start'];
    $end = $dates['end'];
    $pay_date = date('Y-m-d');

    $conn->begin_transaction();
    try {
        // 1. Ensure Period Exists
        $check = $conn->prepare("SELECT period_id FROM workforce_payroll_periods WHERE start_date = ? AND end_date = ? LIMIT 1");
        $check->bind_param("ss", $start, $end);
        $check->execute();
        $ex = $check->get_result()->fetch_assoc();
        $check->close();

        if ($ex) {
            $period_id = $ex['period_id'];
            $conn->query("UPDATE workforce_payroll_periods SET status='Closed' WHERE period_id=$period_id");
        } else {
            $ins = $conn->prepare("INSERT INTO workforce_payroll_periods (start_date, end_date, pay_date, status, created_at) VALUES (?, ?, ?, 'Closed', NOW())");
            $ins->bind_param("sss", $start, $end, $pay_date);
            $ins->execute();
            $period_id = $ins->insert_id;
            $ins->close();
        }

        // 2. Fetch Employees & Attendance Logic (Inline Calculation for Safety)
        $empSql = "SELECT e.employee_id, e.payment_type, e.monthly_salary, COALESCE(e.daily_rate, jt.default_daily_rate, 0) as daily_rate
                   FROM workforce_assignments a JOIN workforce_employees e ON a.employee_id = e.employee_id
                   LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
                   WHERE a.project_id = ? AND a.status = 'Active'";
        $eStmt = $conn->prepare($empSql);
        $eStmt->bind_param("i", $project_id);
        $eStmt->execute();
        $employees = $eStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $insRow = $conn->prepare("INSERT INTO workforce_payroll (period_id, employee_id, hours_worked, gross_pay, net_pay, status) 
                                  VALUES (?, ?, ?, ?, ?, 'Processed')
                                  ON DUPLICATE KEY UPDATE hours_worked=VALUES(hours_worked), gross_pay=VALUES(gross_pay), net_pay=VALUES(net_pay)");

        foreach ($employees as $emp) {
            // Simplified recalc logic for final locking
            $attRes = $conn->query("SELECT time_in, time_out, status FROM workforce_attendance WHERE employee_id={$emp['employee_id']} AND project_id=$project_id AND attendance_date BETWEEN '$start' AND '$end'");
            
            $reg_sec = 0; $ot_sec = 0;
            while($log = $attRes->fetch_assoc()) {
                if($log['status']=='Absent') continue;
                $sec = ($log['status']=='Present' && empty($log['time_out'])) ? 28800 : (strtotime($log['time_out']??0) - strtotime($log['time_in']??0));
                if($sec > 18000) $sec -= 3600;
                $reg = min(28800, max(0, $sec));
                $ot = max(0, $sec - 28800);
                $reg_sec += $reg; $ot_sec += $ot;
            }
            
            $total_hrs = round(($reg_sec + $ot_sec)/3600, 2);
            $reg_hrs = round($reg_sec/3600, 2);
            $ot_hrs = round($ot_sec/3600, 2);

            $hourly = ($emp['payment_type']=='Monthly') ? ($emp['monthly_salary']/26/8) : ($emp['daily_rate']/8);
            $basic = ($emp['payment_type']=='Monthly') ? ($emp['monthly_salary']/2) : ($reg_hrs * $hourly);
            $gross = $basic + ($ot_hrs * $hourly * 1.25);
            
            $basis = ($emp['payment_type']=='Monthly') ? $emp['monthly_salary'] : ($gross*2);
            $deds = calculateGovtDeductions($basis);
            $net = $gross - ($deds['sss']+$deds['ph']+$deds['pi']);

            $insRow->bind_param("iiddd", $period_id, $emp['employee_id'], $total_hrs, $gross, $net);
            $insRow->execute();
        }

        $conn->commit();

        // Log the audit trail
        $period_label = date('M d', strtotime($start)) . '-' . date('d, Y', strtotime($end));
        Logger::init($conn);
        Logger::create('Workforce', "Payroll Locked: Period $period_label for Project #$project_id (" . count($employees) . " employees processed)", $period_id);

        echo json_encode(['success' => true, 'message' => 'Payroll Locked.']);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// Helpers
function calculateGovtDeductions($salary) {
    return [
        'sss' => min($salary * PH_SSS_RATE, PH_SSS_MAX),
        'ph' => $salary * PH_PHILHEALTH_RATE,
        'pi' => min($salary * PH_PAGIBIG_RATE, PH_PAGIBIG_MAX)
    ];
}

function initializeRecord($emp) {
    return [
        'employee_id' => $emp['employee_id'],
        'fullname' => $emp['fullname'],
        'code' => $emp['employee_code'],
        'role' => $emp['role'],
        'payment_type' => $emp['payment_type'] ?? 'Daily',
        'daily_rate' => floatval($emp['daily_rate']),
        'monthly_salary' => floatval($emp['monthly_salary']),
    ];
}

function getPeriodDates($month, $period) {
    if ($period == 1) {
        $start = "$month-01"; $end = "$month-15";
        $label = "1-15 " . date('M Y', strtotime($start));
    } else {
        $start = "$month-16"; $end = date('Y-m-t', strtotime($start));
        $label = "16-" . date('d', strtotime($end)) . " " . date('M Y', strtotime($start));
    }
    return ['start' => $start, 'end' => $end, 'label' => $label];
}
?>