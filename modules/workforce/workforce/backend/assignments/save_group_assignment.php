<?php
require_once '../config.php'; // your PDO connection
header('Content-Type: application/json');

try {
    $group_assignment_id = trim($_POST['group_assignment_id'] ?? '');
    $group_id = trim($_POST['group_id'] ?? '');
    $project_id = trim($_POST['project_id'] ?? '');
    $phase_id = trim($_POST['phase_id'] ?? null);
    $task_description = trim($_POST['task_description'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $status = trim($_POST['status'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
 



    if (!$group_assignment_id) {
        throw new Exception("Group Assignment ID is required");
    }
    if (!$group_id || !$project_id || !$task_description || !$role || !$start_date) {
        throw new Exception("Required fields missing");
    }

    // Check if group assignment already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM group_assignments WHERE group_assignment_id = ?");
    $stmt->execute([$group_assignment_id]);
    $exists = $stmt->fetchColumn() > 0;

    if ($exists) {
        // Update existing group assignment
        $sql = "UPDATE group_assignments SET 
                    group_id = :group_id,
                    project_id = :project_id,
                    phase_id = :phase_id,
                    task_description = :task_description,
                    role = :role,
                    start_date = :start_date,
                    end_date = :end_date,
                    status = :status,
                    notes = :notes
                WHERE group_assignment_id = :group_assignment_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':group_id' => $group_id,
            ':project_id' => $project_id,
            ':phase_id' => $phase_id,
            ':task_description' => $task_description,
            ':role' => $role,
            ':start_date' => $start_date,
            ':end_date' => $end_date ?: null,
            ':notes' => $notes,
             ':status' => $status,
            ':group_assignment_id' => $group_assignment_id
        ]);
        echo json_encode(['success' => true, 'message' => 'Group assignment updated successfully']);
    } else {
        // Insert new group assignment
        $sql = "INSERT INTO group_assignments (
                    group_assignment_id, group_id, project_id, phase_id,
                    task_description, role, start_date, end_date, status,notes
                ) VALUES (
                    :group_assignment_id, :group_id, :project_id, :phase_id,
                    :task_description, :role, :start_date, :end_date, :status, :notes
                )";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':group_assignment_id' => $group_assignment_id,
            ':group_id' => $group_id,
            ':project_id' => $project_id,
            ':phase_id' => $phase_id,
            ':task_description' => $task_description,
            ':role' => $role,
            ':start_date' => $start_date,
            ':end_date' => $end_date ?: null,
             ':status' => $status,
            ':notes' => $notes
        ]);
        echo json_encode(['success' => true, 'message' => 'Group assignment added successfully']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
