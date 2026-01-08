<?php
require_once '../config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? null;

try {
    switch($action) {
        // ---------------- LIST PERIODS ----------------
        case 'list_periods':
            $stmt = $conn->query("SELECT * FROM payroll_periods ORDER BY period_start DESC");
            $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success'=>true,'periods'=>$periods]);
            exit;

     case 'fetch_payroll':
    $period_id = $_GET['period_id'] ?? null;
    if (!$period_id) throw new Exception("Period ID missing");

    // Get pay date from payroll_periods table
    $stmt = $conn->prepare("SELECT period_start, period_end, pay_date FROM payroll_periods WHERE period_id = ?");
    $stmt->execute([$period_id]);
    $period = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$period) throw new Exception("Payroll period not found");
    $pay_date = $period['pay_date'] ?? null;

    // Fetch all active employees
    $stmt = $conn->prepare("SELECT * FROM employees WHERE status='Active'");
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $payrollData = [];
    foreach($employees as $emp){
        // Check if payroll exists for this employee & period
        $stmt = $conn->prepare("SELECT * FROM payroll WHERE employee_id=? AND period_id=?");
        $stmt->execute([$emp['employee_id'], $period_id]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($p) {
            $payrollData[] = [
                'employee_id' => $emp['employee_id'],
                'first_name' => $emp['first_name'],
                'last_name' => $emp['last_name'],
                'position' => $emp['position'],
                'employment_type' => $emp['employment_type'],
                'period_id' => $period_id,
                'pay_date' => $pay_date, // use from payroll_periods
                'hours_worked' => floatval($p['hours_worked']),
                'overtime_hours' => floatval($p['overtime_hours']),
                'gross_pay' => floatval($p['gross_pay']),
                'sss_contribution' => floatval($p['sss_contribution']),
                'philhealth_contribution' => floatval($p['philhealth_contribution']),
                'pagibig_contribution' => floatval($p['pagibig_contribution']),
                'other_deductions' => floatval($p['other_deductions']),
                'net_pay' => floatval($p['net_pay']),
                'status' => $p['status']
            ];
        } else {
            // If payroll not yet calculated
            $payrollData[] = [
                'employee_id' => $emp['employee_id'],
                'first_name' => $emp['first_name'],
                'last_name' => $emp['last_name'],
                'position' => $emp['position'],
                'employment_type' => $emp['employment_type'],
                'period_id' => $period_id,
                'pay_date' => $pay_date, // still use payroll_periods
                'hours_worked' => 0,
                'overtime_hours' => 0,
                'gross_pay' => 0,
                'sss_contribution' => 0,
                'philhealth_contribution' => 0,
                'pagibig_contribution' => 0,
                'other_deductions' => 0,
                'net_pay' => 0,
                'status' => 'Not Calculated'
            ];
        }
    }

    echo json_encode(['success'=>true, 'payroll'=>$payrollData]);
    exit;





        // ---------------- CALCULATE PAYROLL ----------------
      case 'calculate':
            $period_id = $_POST['period_id'] ?? $_GET['period_id'] ?? null;
            if (!$period_id) throw new Exception("Period ID missing");

            // Prevent recalculation if already calculated / approved / processed
            $stmt = $conn->prepare("
                SELECT status 
                FROM payroll_periods 
                WHERE period_id = ?
            ");
            $stmt->execute([$period_id]);
            $currentStatus = $stmt->fetchColumn();


            if (in_array($currentStatus, ['Approved', 'Processed'])) {
                throw new Exception("Payroll is locked and cannot be recalculated");
            }



            // Get payroll period
            $stmt = $conn->prepare("
                SELECT period_start, period_end 
                FROM payroll_periods 
                WHERE period_id = ?
            ");
            $stmt->execute([$period_id]);
            $period = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$period) throw new Exception("Payroll period not found");

            $start = $period['period_start'];
            $end   = $period['period_end'];

            // Active employees
            $stmt = $conn->prepare("SELECT * FROM employees WHERE status='Active'");
            $stmt->execute();
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Payroll config
            $stmt = $conn->prepare("
                SELECT config_name, config_value 
                FROM payroll_config 
                WHERE is_active = 1
            ");
            $stmt->execute();
            $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $result = [];

            foreach ($employees as $emp) {

                $employee_id = $emp['employee_id'];
                $hourly_rate = $emp['daily_rate'] / 8;

                // Attendance summary
                $stmt = $conn->prepare("
                    SELECT 
                        SUM(TIMESTAMPDIFF(MINUTE,time_in,time_out))/60 AS hours_worked,
                        SUM(
                            CASE WHEN status='Late'
                            THEN TIMESTAMPDIFF(MINUTE,time_in,time_out)/60
                            ELSE 0 END
                        ) AS overtime_hours
                    FROM attendance
                    WHERE employee_id = ?
                        AND attendance_date BETWEEN ? AND ?
                ");
                $stmt->execute([$employee_id, $start, $end]);
                $att = $stmt->fetch(PDO::FETCH_ASSOC);

                $hours_worked   = floatval($att['hours_worked'] ?? 0);
                $overtime_hours = floatval($att['overtime_hours'] ?? 0);

                // Computation
                $overtime_rate = floatval($configs['Overtime Multiplier'] ?? 1.25);
                $overtime_pay  = $overtime_hours * $hourly_rate * $overtime_rate;
                $gross_pay     = ($hours_worked * $hourly_rate) + $overtime_pay;

                $sss        = $gross_pay * floatval($configs['SSS Contribution Rate'] ?? 0);
                $philhealth = $gross_pay * floatval($configs['PhilHealth Contribution Rate'] ?? 0);
                $pagibig    = $gross_pay * floatval($configs['PagIBIG Contribution Rate'] ?? 0);

                $net_pay = $gross_pay - ($sss + $philhealth + $pagibig);

                // Save / Update payroll
                $stmt = $conn->prepare("
                    INSERT INTO payroll (
                        payroll_id,
                        employee_id,
                        period_id,
                        hours_worked,
                        overtime_hours,
                        gross_pay,
                        tax_deduction,
                        sss_contribution,
                        philhealth_contribution,
                        pagibig_contribution,
                        other_deductions,
                        net_pay,
                        status
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Calculated'
                    )
                    ON DUPLICATE KEY UPDATE
                        hours_worked = VALUES(hours_worked),
                        overtime_hours = VALUES(overtime_hours),
                        gross_pay = VALUES(gross_pay),
                        sss_contribution = VALUES(sss_contribution),
                        philhealth_contribution = VALUES(philhealth_contribution),
                        pagibig_contribution = VALUES(pagibig_contribution),
                        net_pay = VALUES(net_pay),
                        status = 'Calculated',
                        updated_at = CURRENT_TIMESTAMP
                ");

                $stmt->execute([
                    uniqid('PR-'),
                    $employee_id,
                    $period_id,
                    round($hours_worked,2),
                    round($overtime_hours,2),
                    round($gross_pay,2),
                    0, // tax_deduction
                    round($sss,2),
                    round($philhealth,2),
                    round($pagibig,2),
                    0, // other_deductions
                    round($net_pay,2)
                ]);

                // For frontend refresh
                $result[] = [
                    'employee_id'   => $employee_id,
                    'first_name'    => $emp['first_name'],
                    'last_name'     => $emp['last_name'],
                    'hours_worked'  => round($hours_worked,2),
                    'overtime_hours'=> round($overtime_hours,2),
                    'gross_pay'     => round($gross_pay,2),
                    'net_pay'       => round($net_pay,2),
                    'status'        => 'Calculated'
                ];
            }

            $stmt = $conn->prepare("
                UPDATE payroll_periods
                SET status = 'Calculated'
                WHERE period_id = ?
            ");
            $stmt->execute([$period_id]);

            echo json_encode([
                'success' => true,
                'payroll' => $result
            ]);
            exit;

        // ---------------- APPROVE PAYROLL ----------------
        case 'approve':
                $periodId = $_REQUEST['period_id'] ?? null;

                if (!$periodId) {
                    echo json_encode([
                        "success" => false,
                        "message" => "Period ID missing"
                    ]);
                    exit;
                }

                // Optional: who approved
                $approvedBy = null; // or $_SESSION['user_id']

                // Ensure payroll period is in Calculated status
                $stmt = $conn->prepare("
                    SELECT status 
                    FROM payroll_periods 
                    WHERE period_id = ?
                ");
                $stmt->execute([$periodId]);
                $status = $stmt->fetchColumn();

                if ($status !== 'Calculated') {
                    echo json_encode([
                        "success" => false,
                        "message" => "Only calculated payroll can be approved"
                    ]);
                    exit;
                }


                // Approve payroll
                $stmt = $conn->prepare("
                    UPDATE payroll
                    SET
                        status = 'Approved',
                        approved_by = ?,
                        approved_date = NOW()
                    WHERE period_id = ?
                    AND status = 'Calculated'
                ");
                $stmt->execute([$approvedBy, $periodId]);

                // Close payroll period
                $stmt = $conn->prepare("
                    UPDATE payroll_periods
                    SET status = 'Approved'
                    WHERE period_id = ?
                ");
                $stmt->execute([$periodId]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Payroll approved'
                ]);
                exit;



        // ---------------- PROCESS PAYROLL ----------------
                case 'process':
                $period_id = $_POST['period_id'] ?? null;
                if (!$period_id) throw new Exception("Period ID missing");

                //$processed_by = $_POST['processed_by'] ?? 'admin';

                // Ensure approved first
                $check = $conn->prepare("
                SELECT COUNT(*) 
                FROM payroll 
                WHERE period_id = ?
                AND status = 'Approved'
                ");
                $check->execute([$period_id]);

                if ($check->fetchColumn() == 0) {
                throw new Exception("Payroll must be approved first");
                }

                // Update payroll rows
                $stmt = $conn->prepare("
                UPDATE payroll
                SET 
                status = 'Processed',
                processed_date = NOW()
                WHERE period_id = ?
                AND status = 'Approved'
                ");
                $stmt->execute([ $period_id]);

                // Update period status
                $stmt = $conn->prepare("
                UPDATE payroll_periods
                SET status = 'Processed'
                WHERE period_id = ?
                ");
                $stmt->execute([$period_id]);

                echo json_encode([
                'success' => true,
                'message' => 'Payroll processed'
                ]);
                exit;

        default:
            throw new Exception("Invalid action");
    }

} catch(Exception $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
