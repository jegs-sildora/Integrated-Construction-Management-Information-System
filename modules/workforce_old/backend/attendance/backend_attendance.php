<?php
header('Content-Type: application/json');
require '../config.php'; // PDO connection ($conn)

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Get action from GET, POST, or JSON payload
$action = $_GET['action'] ?? $_POST['action'] ?? ($data['action'] ?? null);

try {

    // ------------------ Check if attendance is locked ------------------
    if ($action === 'check_lock') {
        $date = $_GET['date'] ?? date('Y-m-d');

        $stmt = $conn->prepare("SELECT COUNT(*) AS locked_count FROM attendance WHERE attendance_date = ? AND is_locked = 1");
        $stmt->execute([$date]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'locked' => $res['locked_count'] > 0
        ]);
        exit;
    }

    // ------------------ Add / Update Attendance ------------------
    if ($action === 'add') {

        if (!isset($data['records']) || !is_array($data['records'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }

        $lockDate = $data['lock'] ?? false; // Should we lock the date?

        $sql = "
            INSERT INTO attendance
            (employee_id, attendance_date, time_in, time_out, status, project_id, remarks, is_locked)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                time_in = VALUES(time_in),
                time_out = VALUES(time_out),
                status = VALUES(status),
                project_id = VALUES(project_id),
                remarks = VALUES(remarks),
                is_locked = GREATEST(is_locked, VALUES(is_locked))
        ";

        $stmt = $conn->prepare($sql);

        $conn->beginTransaction();
        foreach ($data['records'] as $rec) {
            $employee_id = $rec['employee_id'] ?? null;
            $attendance_date = $rec['attendance_date'] ?? null;
            $time_in = $rec['time_in'] ?? null;
            $time_out = $rec['time_out'] ?? null;
            $status = $rec['status'] ?? null;
            $project_id = $rec['project_id'] ?? null;
            $remarks = $rec['remarks'] ?? null;

            if (!$employee_id || !$attendance_date || !$status) continue;

            $stmt->execute([
                $employee_id,
                $attendance_date,
                $time_in,
                $time_out,
                $status,
                $project_id,
                $remarks,
                $lockDate ? 1 : 0
            ]);
        }
        $conn->commit();

        echo json_encode(['success' => true]);
        exit;
    }

    // ------------------ Fetch Employee Assignment ------------------
    elseif ($action === 'assignment') {
        $employeeId = $_GET['employee_id'] ?? null;
        $date       = $_GET['date'] ?? date('Y-m-d');

        if (!$employeeId) {
            echo json_encode(['success' => false, 'message' => 'Missing employee ID']);
            exit;
        }

        $sql = "
            SELECT 
                a.assignment_id,
                a.project_id,
                p.project_name
            FROM assignments a
            JOIN projects p ON p.project_id = a.project_id
            WHERE a.employee_id = ?
              AND a.start_date <= ?
            ORDER BY a.start_date DESC
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$employeeId, $date]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'assignment' => $assignment ?: null
        ]);
        exit;
    }

    // ------------------ Unknown Action ------------------
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
