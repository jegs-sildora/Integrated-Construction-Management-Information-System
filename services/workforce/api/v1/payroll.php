<?php
/**
 * Payroll API Engine v1 - Workforce Service
 */
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../Logger.php';

$db = Database::getConnection();

// PH Contribution Config (2025 Standard)
const PH_SSS_RATE = 0.045; 
const PH_SSS_MAX = 1350;   
const PH_PHILHEALTH_RATE = 0.025; 
const PH_PAGIBIG_RATE = 0.02; 
const PH_PAGIBIG_MAX = 200;

try {
    // Handle JSON Input from API Gateway
    if (empty($_POST)) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (is_array($input)) {
            $_POST = $input;
            $_REQUEST = array_merge($_REQUEST, $input);
        }
    }

    $action = $_REQUEST['action'] ?? '';
    $project_id = isset($_REQUEST['project_id']) ? intval($_REQUEST['project_id']) : 0;
    
    if ($project_id === 0) throw new Exception('Project ID is required.');

    switch ($action) {
        case 'get_payroll':
            $month = $_GET['month'] ?? date('Y-m');
            $period = intval($_GET['period'] ?? 1);
            getPayrollData($db, $project_id, $month, $period);
            break;

        case 'lock_payroll':
            $month = $_POST['month'] ?? date('Y-m');
            $period = intval($_POST['period'] ?? 1);
            lockPayrollPeriod($db, $project_id, $month, $period);
            break;
            
        case 'get_history':
            getPayrollHistory($db, $project_id);
            break;

        default:
            throw new Exception('Invalid Action Request');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * ACTION: GET HISTORY
 */
function getPayrollHistory($db, $project_id) {
    $sql = "SELECT 
                p.period_id, 
                p.start_date, 
                p.end_date, 
                p.pay_date, 
                p.status,
                COUNT(wp.payroll_id) as employee_count,
                SUM(wp.gross_pay) as total_gross,
                SUM(wp.net_pay) as total_net
            FROM payroll_periods p
            JOIN payroll wp ON p.period_id = wp.period_id
            JOIN assignments wa ON wp.employee_id = wa.employee_id
            WHERE p.status = 'Closed' 
            AND wa.project_id = ?
            GROUP BY p.period_id, p.start_date, p.end_date, p.pay_date, p.status
            ORDER BY p.end_date DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id]);
    $result = $stmt->fetchAll();
    
    $history = [];
    foreach ($result as $row) {
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
 * ACTION: GET PAYROLL BY PERIOD
 */
function getPayrollByPeriod($db, $period_id, $project_id) {
    if ($period_id === 0) throw new Exception('Period ID is required.');

    // Fetch period info
    $pstmt = $db->prepare("SELECT start_date, end_date, pay_date, status FROM payroll_periods WHERE period_id = ? LIMIT 1");
    $pstmt->execute([$period_id]);
    $period = $pstmt->fetch();

    if (!$period) throw new Exception('Period not found.');

    // Fetch payroll rows
    $sql = "SELECT wp.*, CONCAT(e.first_name, ' ', e.last_name) AS fullname, e.employee_code, COALESCE(e.daily_rate, 0) as daily_rate, e.monthly_salary
            FROM payroll wp
            JOIN employees e ON wp.employee_id = e.employee_id
            " . ($project_id > 0 ? 'JOIN assignments wa ON e.employee_id = wa.employee_id' : '') . "
            WHERE wp.period_id = ? " . ($project_id > 0 ? 'AND wa.project_id = ?' : '') . "
            ORDER BY fullname ASC";

    $stmt = $db->prepare($sql);
    $params = [$period_id];
    if ($project_id > 0) $params[] = $project_id;
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => [
            'period' => $period,
            'rows' => $rows
        ]
    ]);
}

/**
 * ACTION: GET PAYSLIP
 */
function getPayslip($db, $period_id, $employee_id) {
    if ($period_id === 0 || $employee_id === 0) throw new Exception('Period ID and Employee ID are required.');

    $sql = "SELECT wp.*, CONCAT(e.first_name, ' ', e.last_name) AS fullname, e.employee_code AS code, e.daily_rate, e.monthly_salary
            FROM payroll wp
            JOIN employees e ON wp.employee_id = e.employee_id
            WHERE wp.employee_id = ? AND wp.period_id = ? LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$employee_id, $period_id]);
    $row = $stmt->fetch();

    if (!$row) throw new Exception('Payslip not found.');

    // Fetch period label
    $pq = $db->prepare("SELECT start_date, end_date FROM payroll_periods WHERE period_id = ? LIMIT 1");
    $pq->execute([$period_id]);
    $period = $pq->fetch();

    echo json_encode([
        'success' => true,
        'data' => [
            'payroll' => $row,
            'period' => $period
        ]
    ]);
}

/**
 * ACTION: GET PAYROLL RECORD
 */
function getPayrollRecord($db, $payroll_id) {
    if ($payroll_id === 0) throw new Exception('Payroll ID is required.');

    $sql = "SELECT 
                wp.*,
                wpp.pay_date,
                wpp.start_date,
                wpp.end_date,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                e.payment_type,
                e.monthly_salary,
                COALESCE(e.daily_rate, jt.default_daily_rate, 0) as daily_rate,
                jt.title_name as role
            FROM payroll wp
            JOIN employees e ON wp.employee_id = e.employee_id
            JOIN payroll_periods wpp ON wp.period_id = wpp.period_id
            LEFT JOIN job_titles jt ON e.job_title_id = jt.job_title_id
            WHERE wp.payroll_id = ?
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->execute([$payroll_id]);
    $row = $stmt->fetch();

    if (!$row) throw new Exception('Record not found.');

    // We can't join project_phases here because it's in another service.
    // The frontend will have to handle fetching phase name if needed, or we just return phase_id if we had it.
    // Actually, assignments table has phase_id.
    
    $assignQ = $db->prepare("SELECT phase_id FROM assignments WHERE employee_id = ? AND status = 'Active' LIMIT 1");
    $assignQ->execute([$row['employee_id']]);
    $assign = $assignQ->fetch();
    $row['phase_id'] = $assign ? $assign['phase_id'] : null;

    echo json_encode([
        'success' => true,
        'data' => $row
    ]);
}

/**
 * ACTION: GET PAYROLL DATA
 */
function getPayrollData($db, $project_id, $month, $period) {
    $dates = getPeriodDates($month, $period);
    $start_date = $dates['start'];
    $end_date = $dates['end'];
    $period_label = $dates['label'];

    $is_locked = false;
    $period_id = 0;
    
    $checkLock = $db->prepare("SELECT period_id, status FROM payroll_periods 
                                 WHERE start_date = ? AND end_date = ? LIMIT 1");
    $checkLock->execute([$start_date, $end_date]);
    if ($row = $checkLock->fetch()) {
        $is_locked = ($row['status'] === 'Closed');
        $period_id = $row['period_id'];
    }

    $sql = "SELECT 
                e.employee_id, e.employee_code, CONCAT(e.first_name, ' ', e.last_name) as fullname,
                e.payment_type, e.monthly_salary,
                COALESCE(e.daily_rate, jt.default_daily_rate, 0) as daily_rate,
                jt.title_name as role
            FROM assignments a
            JOIN employees e ON a.employee_id = e.employee_id
            LEFT JOIN job_titles jt ON e.job_title_id = jt.job_title_id
            WHERE a.project_id = ? AND a.status = 'Active'
            ORDER BY e.last_name ASC";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id]);
    $employees = $stmt->fetchAll();

    $payroll_data = [];
    $issues = [];
    $totals = ['gross' => 0, 'deductions' => 0, 'net' => 0, 'count' => 0];

    foreach ($employees as $emp) {
        $emp_id = $emp['employee_id'];
        $record = initializeRecord($emp);

        if ($is_locked && $period_id > 0) {
            $savedQ = $db->prepare("SELECT hours_worked, gross_pay, net_pay FROM payroll WHERE period_id = ? AND employee_id = ? LIMIT 1");
            $savedQ->execute([$period_id, $emp_id]);
            $saved = $savedQ->fetch();
            
            if ($saved) {
                $record['hours_worked'] = floatval($saved['hours_worked']);
                $record['gross_pay'] = floatval($saved['gross_pay']);
                $record['net_pay'] = floatval($saved['net_pay']);
                $record['days_worked'] = round($record['hours_worked'] / 8, 2);
                $record['deductions'] = $record['gross_pay'] - $record['net_pay'];
                
                $basis = ($emp['payment_type'] === 'Monthly') ? floatval($emp['monthly_salary']) : ($record['gross_pay'] * 2);
                $deds = calculateGovtDeductions($basis);
                $record['sss_deduction'] = $deds['sss'];
                $record['philhealth_deduction'] = $deds['ph'];
                $record['pagibig_deduction'] = $deds['pi'];
                $record['basic_pay'] = $record['gross_pay'];
            }
        } else {
            $attQ = $db->prepare("SELECT time_in, time_out, status FROM attendance 
                                    WHERE employee_id = ? AND project_id = ? 
                                    AND attendance_date BETWEEN ? AND ?");
            $attQ->execute([$emp_id, $project_id, $start_date, $end_date]);
            
            $reg_sec = 0; $ot_sec = 0; $days_count = 0;

            while ($log = $attQ->fetch()) {
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

            $reg_hrs = round($reg_sec / 3600, 2);
            $ot_hrs = round($ot_sec / 3600, 2);
            
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
function lockPayrollPeriod($db, $project_id, $month, $period) {
    $dates = getPeriodDates($month, $period);
    $start = $dates['start'];
    $end = $dates['end'];
    $pay_date = date('Y-m-d');

    $db->beginTransaction();
    try {
        $check = $db->prepare("SELECT period_id FROM payroll_periods WHERE start_date = ? AND end_date = ? LIMIT 1");
        $check->execute([$start, $end]);
        $ex = $check->fetch();

        if ($ex) {
            $period_id = $ex['period_id'];
            $db->prepare("UPDATE payroll_periods SET status='Closed' WHERE period_id=?")->execute([$period_id]);
        } else {
            $ins = $db->prepare("INSERT INTO payroll_periods (start_date, end_date, pay_date, status, created_at) VALUES (?, ?, ?, 'Closed', CURRENT_TIMESTAMP)");
            $ins->execute([$start, $end, $pay_date]);
            $period_id = $db->lastInsertId();
        }

        $empSql = "SELECT e.employee_id, e.payment_type, e.monthly_salary, COALESCE(e.daily_rate, jt.default_daily_rate, 0) as daily_rate
                   FROM assignments a JOIN employees e ON a.employee_id = e.employee_id
                   LEFT JOIN job_titles jt ON e.job_title_id = jt.job_title_id
                   WHERE a.project_id = ? AND a.status = 'Active'";
        $eStmt = $db->prepare($empSql);
        $eStmt->execute([$project_id]);
        $employees = $eStmt->fetchAll();

        $insRow = $db->prepare("INSERT INTO payroll (period_id, employee_id, hours_worked, gross_pay, net_pay, status) 
                                  VALUES (?, ?, ?, ?, ?, 'Processed')
                                  ON CONFLICT (period_id, employee_id) DO UPDATE SET 
                                  hours_worked = EXCLUDED.hours_worked, 
                                  gross_pay = EXCLUDED.gross_pay, 
                                  net_pay = EXCLUDED.net_pay");

        foreach ($employees as $emp) {
            $attQ = $db->prepare("SELECT time_in, time_out, status FROM attendance WHERE employee_id=? AND project_id=? AND attendance_date BETWEEN ? AND ?");
            $attQ->execute([$emp['employee_id'], $project_id, $start, $end]);
            
            $reg_sec = 0; $ot_sec = 0;
            while($log = $attQ->fetch()) {
                if($log['status']=='Absent') continue;
                $sec = ($log['status']=='Present' && empty($log['time_out'])) ? 28800 : (strtotime($log['time_out']??'0') - strtotime($log['time_in']??'0'));
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

            $insRow->execute([$period_id, $emp['employee_id'], $total_hrs, $gross, $net]);
        }

        $db->commit();
        $period_label = date('M d', strtotime($start)) . '-' . date('d, Y', strtotime($end));
        Logger::create('Workforce', "Payroll Locked: Period $period_label for Project #$project_id (" . count($employees) . " employees processed)", $period_id);

        echo json_encode(['success' => true, 'message' => 'Payroll Locked.']);
    } catch (Exception $e) {
        $db->rollBack();
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
