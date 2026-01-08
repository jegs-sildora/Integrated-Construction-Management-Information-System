<?php
require '../config.php';
header('Content-Type: application/json');

try {

    /* ================= EMPLOYEES STATS ================= */
    $empTotal = $conn->query("SELECT COUNT(*) FROM employees")->fetchColumn();
    $empActive = $conn->query("SELECT COUNT(*) FROM employees WHERE status='Active'")->fetchColumn();
    $empOnLeave = $conn->query("SELECT COUNT(*) FROM employees WHERE status='On Leave'")->fetchColumn();

    /* ================= ASSIGNMENT UTILIZATION ================= */
    // Count total employees
            $empTotal = $conn->query("SELECT COUNT(*) FROM employees")->fetchColumn();

            // Count distinct employees with active assignments
            $assigned = $conn->query("
                SELECT COUNT(DISTINCT employee_id) 
                FROM assignments
                WHERE status = 'Active'
                AND (end_date IS NULL OR end_date >= CURDATE())
            ")->fetchColumn();

            // Calculate unassigned
            $unassigned = $empTotal - $assigned;

            // Utilization rate (%)
            $utilizationRate = $empTotal > 0 ? round(($assigned / $empTotal) * 100) : 0;


    /* ================= ATTENDANCE TODAY ================= */
    $attendanceToday = $conn->query("
        SELECT 
            SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present,
            SUM(CASE WHEN status='Absent' THEN 1 ELSE 0 END) AS absent
        FROM attendance
        WHERE attendance_date = CURDATE()
    ")->fetch(PDO::FETCH_ASSOC);

    $totalAttendance = ($attendanceToday['present'] + $attendanceToday['absent']) ?: 1;
    $attendanceRate = round(($attendanceToday['present'] / $totalAttendance) * 100);

    /* ================= ATTENDANCE TREND (LAST 7 DAYS) ================= */
    $attendanceTrend = [];
    for ($i = 6; $i >= 0; $i--) {
        $stmt = $conn->prepare("
            SELECT 
                SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present,
                COUNT(*) AS total
            FROM attendance
            WHERE attendance_date = CURDATE() - INTERVAL ? DAY
        ");
        $stmt->execute([$i]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $attendanceTrend[] = ($row['total'] > 0) ? round(($row['present'] / $row['total']) * 100) : 0;
    }

    /* ================= PAYROLL ================= */
        $pay = $conn->query("
            SELECT 
                COALESCE(SUM(net_pay), 0) AS total,
                COALESCE(SUM(CASE WHEN status='Paid' THEN net_pay ELSE 0 END), 0) AS paid,
                COALESCE(SUM(CASE WHEN status='Pending' THEN net_pay ELSE 0 END), 0) AS pending
            FROM payroll
            WHERE MONTH(calculated_date) = MONTH(CURDATE())
            AND YEAR(calculated_date) = YEAR(CURDATE())
        ")->fetch(PDO::FETCH_ASSOC);


    /* ================= FINAL RESPONSE ================= */
    echo json_encode([
        "success" => true,
        "stats" => [
            "employees" => [
                "total"    => (int)$empTotal,
                "active"   => (int)$empActive,
                "inactive" => (int)$empOnLeave
            ],
            "attendance" => [
                "today"   => $attendanceRate,
                "present" => (int)$attendanceToday['present'],
                "absent"  => (int)$attendanceToday['absent']
            ],
            "utilization" => [
                "rate" => $utilizationRate,
                "assigned" => (int)$assigned,
                "unassigned" => (int)$unassigned
            ],
            "payroll" => $pay
        ],
        "charts" => [
            "attendanceTrend" => $attendanceTrend
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
