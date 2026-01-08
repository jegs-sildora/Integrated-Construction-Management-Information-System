<?php
require '../config.php';
header('Content-Type: application/json');

try {
    // -------------------- FETCH SINGLE EMPLOYEE PAYROLL --------------------
    if (isset($_GET['fetch_id'])) {
        $employeeID = $_GET['fetch_id'];

        // 1. Get employee info (daily_rate / monthly_salary / status / start_date)
        $stmt = $conn->prepare("
            SELECT employee_id, first_name, last_name, daily_rate, monthly_salary, start_date, end_date, status
            FROM employees 
            WHERE employee_id = ?
        ");
        $stmt->execute([$employeeID]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            echo json_encode(['success' => false, 'message' => 'Employee not found']);
            exit;
        }

        // 2. Fetch attendance for payroll calculation
        $stmt = $conn->prepare("
            SELECT 
                attendance_date,
                IF(time_out IS NOT NULL AND time_in IS NOT NULL,
                    ROUND(TIME_TO_SEC(TIMEDIFF(time_out, time_in)) / 3600, 2),
                    0
                ) AS hours_worked,
                status,
                remarks
            FROM attendance
            WHERE employee_id = :id
            ORDER BY attendance_date ASC
        ");
        $stmt->execute(['id' => $employeeID]);
        $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Prepare payroll summary (aggregate totals)
        $totalDaysWorked = 0;
        $totalHours = 0;
        $totalOvertime = 0;

        foreach ($attendanceRecords as $rec) {
            $hours = floatval($rec['hours_worked']);
            $status = strtolower($rec['status']);

            // Count only present/late/overtime as worked day
            if (in_array($status, ['present', 'late', 'overtime'])) {
                $totalDaysWorked++;
            }

            $totalHours += $hours;

            // Overtime: hours beyond 8/day
            if ($hours > 8) {
                $totalOvertime += ($hours - 8);
            }
        }

        // 4. Calculate payroll amounts
        $dailyRate = floatval($employee['daily_rate'] ?? 0);
        $grossPay = ($totalDaysWorked * $dailyRate) + ($totalOvertime * ($dailyRate / 8)); // hourly rate for overtime
        $deductions = round($grossPay * 0.10, 2); // example 10% deduction
        $netPay = round($grossPay - $deductions, 2);

        // 5. Return JSON
        echo json_encode([
            'success' => true,
            'employee' => [
                'info' => $employee,
                'attendance_summary' => [
                    'days_worked' => $totalDaysWorked,
                    'total_hours' => round($totalHours, 2),
                    'overtime_hours' => round($totalOvertime, 2),
                ],
                'payroll_summary' => [
                    'gross_pay' => round($grossPay, 2),
                    'deductions' => $deductions,
                    'net_pay' => $netPay
                ],
                'attendance_records' => $attendanceRecords
            ]
        ]);

        exit;
    }

    // If no fetch_id, return error
    echo json_encode(['success' => false, 'message' => 'Employee ID required']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
