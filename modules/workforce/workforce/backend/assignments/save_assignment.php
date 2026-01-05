<?php
require_once '../config.php'; // your PDO connection
// session_start();
header('Content-Type: application/json');

try {
    $assignment_id = trim($_POST['assignment_id'] ?? '');
    $employee_id = trim($_POST['employee_id'] ?? '');
    $project_id = trim($_POST['project_id'] ?? '');
    $phase_id = trim($_POST['phase_id'] ?? null);
    $task_name = trim($_POST['task_name'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $notes = trim($_POST['notes'] ?? '');
    
    // Automatically set assigned_by from session (replace 'user_id' with your session key)
     $assigned_by = $_SESSION['user_id'] ?? null;
   // if (!$assigned_by) {
   //     throw new Exception("User not logged in");
 //   }

    if (!$assignment_id) {
        throw new Exception("Assignment ID is required");
    }
    if (!$employee_id || !$project_id || !$phase_id || !$task_name || !$role || !$start_date) {
        throw new Exception("Required fields missing");
    }

    // Check if assignment already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM assignments WHERE assignment_id = ?");
    $stmt->execute([$assignment_id]);
    $exists = $stmt->fetchColumn() > 0;

    if ($exists) {
        // Update existing assignment
        $sql = "UPDATE assignments SET 
                    employee_id = :employee_id,
                    project_id = :project_id,
                    phase_id = :phase_id,
                    task_description = :task_description,
                    role = :role,
                    start_date = :start_date,
                    end_date = :end_date,
                    notes = :notes
                WHERE assignment_id = :assignment_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':employee_id' => $employee_id,
            ':project_id' => $project_id,
            ':phase_id' => $phase_id,
            ':task_description' => $task_name,
            ':role' => $role,
            ':start_date' => $start_date,
            ':end_date' => $end_date ?: null,
            ':notes' => $notes,
            ':assignment_id' => $assignment_id
        ]);
        echo json_encode(['success' => true, 'message' => 'Assignment updated successfully']);
    } else {
        // Insert new assignment
        $sql = "INSERT INTO assignments (
                    assignment_id, employee_id, project_id, phase_id,
                    task_description, role, start_date, end_date, notes, assigned_by
                ) VALUES (
                    :assignment_id, :employee_id, :project_id, :phase_id,
                    :task_description, :role, :start_date, :end_date, :notes, :assigned_by
                )";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':assignment_id' => $assignment_id,
            ':employee_id' => $employee_id,
            ':project_id' => $project_id,
            ':phase_id' => $phase_id,
            ':task_description' => $task_name,
            ':role' => $role,
            ':start_date' => $start_date,
            ':end_date' => $end_date ?: null,
            ':notes' => $notes,
            ':assigned_by' => $assigned_by
        ]);
        echo json_encode(['success' => true, 'message' => 'Assignment added successfully']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
