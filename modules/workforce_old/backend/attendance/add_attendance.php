<?php
header('Content-Type: application/json');
require '../config.php'; // PDO connection ($conn)

// Read JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['records']) || !is_array($data['records'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Prepare insert with ON DUPLICATE KEY UPDATE
$sql = "
    INSERT INTO attendance 
    (employee_id, attendance_date, time_in, time_out, status, project_id, remarks)
    VALUES (:employee_id, :attendance_date, :time_in, :time_out, :status, :project_id, :remarks)
    ON DUPLICATE KEY UPDATE
        time_in = VALUES(time_in),
        time_out = VALUES(time_out),
        status = VALUES(status),
        project_id = VALUES(project_id),
        remarks = VALUES(remarks)
";
$stmt = $conn->prepare($sql);

try {
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
            ':employee_id' => $employee_id,
            ':attendance_date' => $attendance_date,
            ':time_in' => $time_in,
            ':time_out' => $time_out,
            ':status' => $status,
            ':project_id' => $project_id,
            ':remarks' => $remarks
        ]);
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
