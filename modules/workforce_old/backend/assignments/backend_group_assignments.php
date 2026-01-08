<?php
require '../config.php'; // PDO connection
header('Content-Type: application/json');

try {
    // -------------------- DELETE --------------------
    if (isset($_POST['delete_id'])) {
        $id = $_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM group_assignments WHERE group_assignment_id = :id");
        $stmt->execute(['id' => $id]);

        echo json_encode([
            'success' => $stmt->rowCount() > 0,
            'message' => $stmt->rowCount() > 0 ? 'Group assignment deleted successfully' : 'Group assignment not found'
        ]);
        exit;
    }

    // -------------------- GET NEXT ID --------------------
    if (isset($_GET['get_next_id'])) {
        $stmt = $conn->query("SELECT group_assignment_id FROM group_assignments ORDER BY group_assignment_id DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $lastId = intval(substr($row['group_assignment_id'], 4));
            $nextId = "GASN" . str_pad($lastId + 1, 3, "0", STR_PAD_LEFT);
        } else {
            $nextId = "GASN001";
        }

        echo json_encode(['success' => true, 'next_id' => $nextId]);
        exit;
    }

    // -------------------- FETCH SINGLE RECORD --------------------
    if (isset($_GET['fetch_id'])) {
        $id = $_GET['fetch_id'];
        $stmt = $conn->prepare("SELECT * FROM group_assignments WHERE group_assignment_id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => (bool)$record,
            'record' => $record ?? null,
            'message' => $record ? 'Group assignment fetched successfully' : 'Group assignment not found'
        ]);
        exit;
    }

    // -------------------- ADD / EDIT --------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
        $group_assignment_id = $_POST['group_assignment_id'] ?? '';
        $group_id            = $_POST['group_id'] ?? '';
        $project_id          = $_POST['project_id'] ?? '';
        $phase_id            = $_POST['phase_id'] ?? null;
        $task_description    = $_POST['task_description'] ?? '';
        $role                = $_POST['role'] ?? '';
        $start_date          = $_POST['start_date'] ?? null;
        $end_date            = $_POST['end_date'] ?? null;
        $status              = $_POST['status'] ?? '';
        $notes               = $_POST['notes'] ?? '';

        if (!$group_assignment_id || !$group_id || !$project_id || !$task_description || !$role || !$start_date) {
            echo json_encode(['success' => false, 'message' => 'Required fields missing']);
            exit;
        }

        // Check if record exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM group_assignments WHERE group_assignment_id = ?");
        $stmt->execute([$group_assignment_id]);
        $exists = $stmt->fetchColumn() > 0;

        if ($exists) {
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
                ':status' => $status,
                ':notes' => $notes,
                ':group_assignment_id' => $group_assignment_id
            ]);
            echo json_encode(['success' => true, 'message' => 'Group assignment updated successfully']);
        } else {
            $sql = "INSERT INTO group_assignments (
                        group_assignment_id, group_id, project_id, phase_id,
                        task_description, role, start_date, end_date, status, notes
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
        exit;
    }

    // -------------------- FETCH ALL RECORDS --------------------
    $stmt = $conn->query("
        SELECT 
            ga.*,
            g.group_name,
            g.group_leader_id,
            p.project_name,
            ph.phase_name
        FROM group_assignments ga
        LEFT JOIN employee_groups g ON ga.group_id = g.group_id
        LEFT JOIN projects p ON ga.project_id = p.project_id
        LEFT JOIN project_phases ph ON ga.phase_id = ph.phase_id
        ORDER BY ga.start_date DESC
    ");

    $records = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Get group leader name
        $leaderName = '';
        if (!empty($row['group_leader_id'])) {
            $leaderStmt = $conn->prepare("SELECT first_name, last_name FROM employees WHERE employee_id = ?");
            $leaderStmt->execute([$row['group_leader_id']]);
            $leader = $leaderStmt->fetch(PDO::FETCH_ASSOC);
            if ($leader) $leaderName = $leader['first_name'] . ' ' . $leader['last_name'];
        }

        $records[] = array_merge($row, ['group_leader_name' => $leaderName]);
    }

    echo json_encode(['success' => true, 'records' => $records]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
