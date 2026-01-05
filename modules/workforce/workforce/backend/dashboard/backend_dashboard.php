<?php
/**
 * ================= DASHBOARD BACKEND =================
 * Module: Labor & Workforce Dashboard
 * Description: Aggregates stats & chart data
 * Payroll: UNDER CONSTRUCTION (commented out safely)
 * =====================================================
 */

require '../config.php';
header('Content-Type: application/json');

try {

    /* =====================================================
       EMPLOYEES STATS
    ===================================================== 
    $empTotal = $conn->query("SELECT COUNT(*) FROM employees")->fetchColumn();

    $empActive = $conn->query("
        SELECT COUNT(*) FROM employees WHERE status = 'Active'
    ")->fetchColumn();

    $empOnLeave = $conn->query("
        SELECT COUNT(*) FROM employees WHERE status = 'On Leave'
    ")->fetchColumn();

            */
    /* =====================================================
       PROJECT STATS
    ===================================================== */
    $projTotal = $conn->query("SELECT COUNT(*) FROM projects")->fetchColumn();

    $projActive = $conn->query("
        SELECT COUNT(*) FROM projects WHERE status = 'In Progress'
    ")->fetchColumn();

    $projPlanning = $conn->query("
        SELECT COUNT(*) FROM projects WHERE status = 'Planning'
    ")->fetchColumn();

    $projCompleted = $conn->query("
        SELECT COUNT(*) FROM projects WHERE status = 'Completed'
    ")->fetchColumn();

    $projOnHold = $conn->query("
        SELECT COUNT(*) FROM projects WHERE status = 'On Hold'
    ")->fetchColumn();


    /* =====================================================
       ATTENDANCE STATS (TODAY)
    ===================================================== 
    $attendanceToday = $conn->query("
        SELECT 
            SUM(status='Present') AS present,
            SUM(status='Absent') AS absent
        FROM attendance
        WHERE date = CURDATE()
    ")->fetch(PDO::FETCH_ASSOC);

    $totalAttendance = ($attendanceToday['present'] + $attendanceToday['absent']) ?: 1;
    $attendanceRate = round(($attendanceToday['present'] / $totalAttendance) * 100);

*/
    /* =====================================================
       ATTENDANCE TREND (LAST 7 DAYS)
    ===================================================== 
    $attendanceTrend = [];

    for ($i = 6; $i >= 0; $i--) {
        $stmt = $conn->prepare("
            SELECT 
                SUM(status='Present') present,
                COUNT(*) total
            FROM attendance
            WHERE date = CURDATE() - INTERVAL ? DAY
        ");
        $stmt->execute([$i]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row['total'] > 0) {
            $attendanceTrend[] = round(($row['present'] / $row['total']) * 100);
        } else {
            $attendanceTrend[] = 0;
        }
    }

*/
    /* =====================================================
       PAYROLL (UNDER CONSTRUCTION)
       COMMENTED OUT SAFELY
    ===================================================== */

    // $pay = $conn->query("
    //   SELECT 
    //     SUM(amount) total,
    //     SUM(CASE WHEN status='Paid' THEN amount ELSE 0 END) paid,
    //     SUM(CASE WHEN status='Pending' THEN amount ELSE 0 END) pending
    //   FROM payroll
    //   WHERE MONTH(pay_date)=MONTH(CURDATE())
    // ")->fetch(PDO::FETCH_ASSOC);

    // TEMP PLACEHOLDER
    $pay = [
        "total"   => 0,
        "paid"    => 0,
        "pending" => 0
    ];


    /* =====================================================
       FINAL RESPONSE
    ===================================================== */
    echo json_encode([
        "success" => true,

        "stats" => [
             /*
            "employees" => [
                "total"    => (int)$empTotal,
                "active"   => (int)$empActive,
                "on_leave" => (int)$empOnLeave
            ],*/
            "projects" => [
                "total"  => (int)$projTotal,
                "active" => (int)$projActive
            ],/*
            "attendance" => [
                "today"   => $attendanceRate,
                "present" => (int)$attendanceToday['present'],
                "absent"  => (int)$attendanceToday['absent']
            ],*/
            "payroll" => $pay
        ],

        "charts" => [
          /*  "attendanceTrend" => $attendanceTrend,*/
            "projectStatus" => [
                "planning"    => (int)$projPlanning,
                "in_progress" => (int)$projActive,
                "completed"   => (int)$projCompleted,
                "on_hold"     => (int)$projOnHold
            ]
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
