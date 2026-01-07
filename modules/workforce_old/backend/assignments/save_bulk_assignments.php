<?php
require '../config.php';
header('Content-Type: application/json');

$data = $_POST;

// Get common fields
$employee_ids = $data['employee_ids'] ?? []; // array of selected employee IDs
$project_id   = $data['project_id'] ?? null;
$phase_id     = $data['phase_id'] ?? null;
$task_name    = $data['task_name'] ?? null;
$role         = $data['role'] ?? null;
$start_date   = $data['start_date'] ?? null;
$end_date     = $data['end_date'] ?? null;
$notes        = $data['notes'] ?? null;

// Validate required fields
if (empty($employee_ids) || !$project_id || !$phase_id || !$task_name || !$role || !$start_date) {
    echo json_encode(['success' => false, 'message' => 'Required fields missing']);
    exit;
}

try {
    foreach ($employee_ids as $employee_id) {
        // Generate next assignment ID automatically
        $stmt = $conn->query("SELECT assignment_id FROM assignments ORDER BY assignment_id DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $lastId = $row ? intval(substr($row['assignment_id'], 3)) : 0;
        $newId = "ASN" . str_pad($lastId + 1, 3, "0", STR_PAD_LEFT);

        // Insert assignment for this employee
        $stmt = $conn->prepare("
            INSERT INTO assignments 
            (assignment_id, employee_id, project_id, phase_id, task_description, role, start_date, end_date, notes) 
            VALUES 
            (:assignment_id, :employee_id, :project_id, :phase_id, :task_description, :role, :start_date, :end_date, :notes)
        ");
        $stmt->execute([
            ':assignment_id'   => $newId,
            ':employee_id'     => $employee_id,
            ':project_id'      => $project_id,
            ':phase_id'        => $phase_id,
            ':task_description'=> $task_name,
            ':role'            => $role,
            ':start_date'      => $start_date,
            ':end_date'        => $end_date ?: null,
            ':notes'           => $notes
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Bulk assignments added successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
