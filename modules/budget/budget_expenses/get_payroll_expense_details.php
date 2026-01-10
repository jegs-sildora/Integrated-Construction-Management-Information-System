<?php
/**
 * API: Get Payroll Expense Details
 * Location: /budget/budget_expenses/get_payroll_expense_details.php
 * Logic: Fetches payroll record and reconstructs deduction breakdown for display.
 */

header('Content-Type: application/json');

// 1. Include Config & Database
// Adjust path assuming location: /modules/budget/budget_expenses/
if (file_exists(__DIR__ . '/../../../config/config.php')) {
    include_once __DIR__ . '/../../../config/config.php';
}
if (file_exists(__DIR__ . '/../../../config/database.php')) {
    include_once __DIR__ . '/../../../config/database.php';
}

// 2. Establish Connection
if (function_exists('getBudgetConnection')) {
    $conn = getBudgetConnection();
} else {
    // Fallback if global function not available
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
}

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// 3. Validation
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
    exit;
}

$payroll_id = intval($_GET['id']);

// 4. PH Contribution Config (Same as Payroll Engine for consistency)
const PH_SSS_RATE = 0.045; 
const PH_SSS_MAX = 1350;   
const PH_PHILHEALTH_RATE = 0.025; 
const PH_PAGIBIG_RATE = 0.02; 
const PH_PAGIBIG_MAX = 200;

try {
    // 5. Fetch Data
    // Join Payroll -> Employees -> Job Titles -> Periods -> Assignments (for Phase)
    $sql = "SELECT 
                wp.payroll_id,
                wp.gross_pay,
                wp.net_pay,
                wp.hours_worked,
                wpp.pay_date,
                wpp.start_date,
                wpp.end_date,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                e.payment_type,
                e.monthly_salary,
                COALESCE(e.daily_rate, jt.default_daily_rate) as daily_rate,
                jt.title_name as role,
                pp.phase_name
            FROM workforce_payroll wp
            JOIN workforce_employees e ON wp.employee_id = e.employee_id
            JOIN workforce_payroll_periods wpp ON wp.period_id = wpp.period_id
            LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
            LEFT JOIN workforce_assignments wa ON e.employee_id = wa.employee_id AND wa.status = 'Active'
            LEFT JOIN icmis_project_phases pp ON wa.phase_id = pp.phase_id
            WHERE wp.payroll_id = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $payroll_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        
        // Format Dates
        $start = date('M j', strtotime($row['start_date']));
        $end = date('j, Y', strtotime($row['end_date']));
        $pay_date = date('F j, Y', strtotime($row['pay_date']));
        
        // Re-calculate Deduction Breakdown
        // Since we only store Total Gross and Net, we reconstruct the breakdown
        // using the same logic as the Payroll Engine to show the user what made up the deductions.
        
        $gross = floatval($row['gross_pay']);
        $net = floatval($row['net_pay']);
        $total_deductions = $gross - $net;
        
        // Determine basis for contributions
        $basis = 0;
        if ($row['payment_type'] === 'Monthly') {
            $basis = floatval($row['monthly_salary']);
        } else {
            // For daily, estimate monthly equivalent or use gross * 2 (standard approx)
            $basis = $gross * 2; 
        }

        // Calculate specific amounts
        $sss = min($basis * PH_SSS_RATE, PH_SSS_MAX);
        $ph = $basis * PH_PHILHEALTH_RATE;
        $pi = min($basis * PH_PAGIBIG_RATE, PH_PAGIBIG_MAX);
        
        // Adjust logic: The stored Net Pay is the absolute truth.
        // If our estimated breakdown doesn't match the actual total deduction exactly (due to other manual deductions),
        // we adjust the display or just show standard contributions.
        // For this view, we will show the standard contributions and group any remainder as "Other/Tax".
        
        $calc_total = $sss + $ph + $pi;
        $remainder = $total_deductions - $calc_total;

        // If remainder is negative (rare), it means actual deductions were less than standard (maybe partial month).
        // In that case, scale down to fit total.
        if ($total_deductions < $calc_total && $total_deductions > 0) {
            $ratio = $total_deductions / $calc_total;
            $sss *= $ratio;
            $ph *= $ratio;
            $pi *= $ratio;
            $remainder = 0;
        }

        $response = [
            'payroll_id' => $row['payroll_id'],
            'employee_name' => $row['employee_name'],
            'role' => $row['role'],
            'phase' => $row['phase_name'],
            'pay_date' => $pay_date,
            'period_label' => "$start - $end",
            'gross_pay' => number_format($gross, 2),
            'net_pay' => number_format($net, 2),
            'total_deductions' => number_format($total_deductions, 2),
            'deductions_breakdown' => [
                'sss' => number_format($sss, 2),
                'ph' => number_format($ph, 2),
                'pi' => number_format($pi, 2),
                'other' => $remainder > 0.01 ? number_format($remainder, 2) : '0.00'
            ]
        ];

        echo json_encode(['success' => true, 'data' => $response]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Record not found']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>