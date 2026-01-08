<?php
require '../config.php'; // PDO connection
header('Content-Type: application/json');
session_start();

try {
    // -------------------- DELETE ASSIGNMENT --------------------
    if (isset($_POST['delete_id'])) {
        $assignment_id = $_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM assignments WHERE assignment_id = :id");
        $stmt->execute([':id' => $assignment_id]);

        echo json_encode([
            'success' => $stmt->rowCount() > 0,
            'message' => $stmt->rowCount() > 0 ? 'Assignment deleted successfully' : 'Assignment not found'
        ]);
        exit;
    }

    // -------------------- GET NEXT ASSIGNMENT ID --------------------
    if (isset($_GET['get_next_id'])) {
        $stmt = $conn->query("SELECT assignment_id FROM assignments ORDER BY assignment_id DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $lastId = $row ? intval(substr($row['assignment_id'], 3)) : 0;
        $newId = "ASN" . str_pad($lastId + 1, 3, "0", STR_PAD_LEFT);

        echo json_encode([
            'success' => true,
            'next_id' => $newId
        ]);
        exit;
    }

    // -------------------- FETCH SINGLE ASSIGNMENT --------------------
    if (isset($_GET['fetch_id'])) {
        $assignment_id = $_GET['fetch_id'];
        $stmt = $conn->prepare("
            SELECT a.*, e.first_name, e.last_name, p.project_name, ph.phase_name
            FROM assignments a
            INNER JOIN employees e ON a.employee_id = e.employee_id
            INNER JOIN projects p ON a.project_id = p.project_id
            LEFT JOIN project_phases ph ON a.phase_id = ph.phase_id
            WHERE a.assignment_id = ?
        ");
        $stmt->execute([$assignment_id]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => (bool)$assignment,
            'assignment' => $assignment ?: null,
            'message' => $assignment ? 'Assignment fetched successfully' : 'Assignment not found'
        ]);
        exit;
    }

    // -------------------- ADD / EDIT ASSIGNMENT --------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
        $assignment_id = trim($_POST['assignment_id'] ?? '');
        $employee_id = trim($_POST['employee_id'] ?? '');
        $project_id = trim($_POST['project_id'] ?? '');
        $phase_id = trim($_POST['phase_id'] ?? null);
        $task_name = trim($_POST['task_name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $notes = trim($_POST['notes'] ?? '');
        $assigned_by = $_SESSION['user_id'] ?? null;

        if (!$assignment_id || !$employee_id || !$project_id || !$phase_id || !$task_name || !$role || !$start_date) {
            throw new Exception("Required fields missing");
        }

        // Check if assignment exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM assignments WHERE assignment_id = ?");
        $stmt->execute([$assignment_id]);
        $exists = $stmt->fetchColumn() > 0;

        if ($exists) {
            // Update
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
            // Insert
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
        exit;
    }

    // -------------------- BULK ASSIGNMENT --------------------
    if (isset($_POST['employee_ids']) && is_array($_POST['employee_ids'])) {
        $employee_ids = $_POST['employee_ids'];
        $project_id   = $_POST['project_id'] ?? null;
        $phase_id     = $_POST['phase_id'] ?? null;
        $task_name    = $_POST['task_name'] ?? null;
        $role         = $_POST['role'] ?? null;
        $start_date   = $_POST['start_date'] ?? null;
        $end_date     = $_POST['end_date'] ?? null;
        $notes        = $_POST['notes'] ?? null;

        if (!$employee_ids || !$project_id || !$phase_id || !$task_name || !$role || !$start_date) {
            throw new Exception("Required fields missing");
        }

        foreach ($employee_ids as $employee_id) {
            $stmt = $conn->query("SELECT assignment_id FROM assignments ORDER BY assignment_id DESC LIMIT 1");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $lastId = $row ? intval(substr($row['assignment_id'], 3)) : 0;
            $newId = "ASN" . str_pad($lastId + 1, 3, "0", STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO assignments 
                (assignment_id, employee_id, project_id, phase_id, task_description, role, start_date, end_date, notes) 
                VALUES (:assignment_id, :employee_id, :project_id, :phase_id, :task_description, :role, :start_date, :end_date, :notes)");
            $stmt->execute([
                ':assignment_id' => $newId,
                ':employee_id' => $employee_id,
                ':project_id' => $project_id,
                ':phase_id' => $phase_id,
                ':task_description' => $task_name,
                ':role' => $role,
                ':start_date' => $start_date,
                ':end_date' => $end_date ?: null,
                ':notes' => $notes
            ]);
        }

        echo json_encode(['success' => true, 'message' => 'Bulk assignments added successfully']);
        exit;
    }

    // -------------------- FETCH ALL ASSIGNMENTS --------------------
    $stmt = $conn->prepare("
        SELECT 
            a.assignment_id,
            e.first_name,
            e.last_name,
            e.employee_id,
            a.role,
            a.start_date,
            a.end_date,
            a.status,
            p.project_name,
            a.task_description AS task_name,
            ph.phase_name
        FROM assignments a
        INNER JOIN employees e ON a.employee_id = e.employee_id
        INNER JOIN projects p ON a.project_id = p.project_id
        LEFT JOIN project_phases ph ON a.phase_id = ph.phase_id
        ORDER BY e.employee_id ASC
    ");
    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format for frontend
    $response = [];
    foreach ($assignments as $a) {
        $initials = strtoupper(substr($a['first_name'], 0, 1) . substr($a['last_name'], 0, 1));
        $avatarColor = "#" . substr(md5($a['employee_id']), 0, 6);

        $response[] = [
            'assignment_id' => $a['assignment_id'],
            'employee_name' => $a['first_name'] . " " . $a['last_name'],
            'employee_id' => $a['employee_id'],
            'initials' => $initials,
            'avatar_color' => $avatarColor,
            'project_name' => $a['project_name'],
            'task_name' => $a['task_name'],
            'phase_name' => $a['phase_name'],
            'role' => $a['role'],
            'start_date' => $a['start_date'],
            'end_date' => $a['end_date'],
            'status' => $a['status']
        ];
    }

    echo json_encode(['success' => true, 'assignments' => $response]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
