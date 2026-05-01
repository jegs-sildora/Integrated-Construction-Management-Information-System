<?php
/**
 * API: Get Payroll Expense Details
 * Location: /budget/budget_expenses/get_payroll_expense_details.php
 * Logic: Fetches payroll record and reconstructs deduction breakdown for display.
 * Refactored to use ApiHelper for microservices communication.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../core/ApiHelper.php';

// 1. Validation
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
    exit;
}

$payroll_id = intval($_GET['id']);

// 2. PH Contribution Config (Same as Payroll Engine for consistency)
const PH_SSS_RATE = 0.045; 
const PH_SSS_MAX = 1350;   
const PH_PHILHEALTH_RATE = 0.025; 
const PH_PAGIBIG_RATE = 0.02; 
const PH_PAGIBIG_MAX = 200;

try {
    // 3. Fetch Data from Workforce Service
    $apiRes = ApiHelper::get("workforce/payroll?action=get_payroll_record&id=$payroll_id&project_id=1"); // project_id dummy as it's required by payroll.php try block
    
    if (!$apiRes['data'] || !isset($apiRes['data']['success']) || !$apiRes['data']['success']) {
        echo json_encode(['success' => false, 'message' => $apiRes['data']['message'] ?? 'Record not found']);
        exit;
    }

    $row = $apiRes['data']['data'];

    // 4. Handle Phase Name (Fetch from Project Service)
    $phase_name = 'General';
    if (!empty($row['phase_id'])) {
        $phaseRes = ApiHelper::get("project/phases?id=" . $row['phase_id']);
        if ($phaseRes['status'] === 200 && isset($phaseRes['data']['phase'])) {
            $phase_name = $phaseRes['data']['phase']['phase_name'];
        }
    }

    // 5. Format Dates
    $start = date('M j', strtotime($row['start_date']));
    $end = date('j, Y', strtotime($row['end_date']));
    $pay_date = date('F j, Y', strtotime($row['pay_date']));
    
    // 6. Re-calculate Deduction Breakdown
    $gross = floatval($row['gross_pay']);
    $net = floatval($row['net_pay']);
    $total_deductions = $gross - $net;
    
    $basis = 0;
    if ($row['payment_type'] === 'Monthly') {
        $basis = floatval($row['monthly_salary']);
    } else {
        $basis = $gross * 2; 
    }

    $sss = min($basis * PH_SSS_RATE, PH_SSS_MAX);
    $ph = $basis * PH_PHILHEALTH_RATE;
    $pi = min($basis * PH_PAGIBIG_RATE, PH_PAGIBIG_MAX);
    
    $calc_total = $sss + $ph + $pi;
    $remainder = $total_deductions - $calc_total;

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
        'phase' => $phase_name,
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

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
