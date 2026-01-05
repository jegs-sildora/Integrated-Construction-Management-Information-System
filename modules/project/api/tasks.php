<?php
/**
 * ========================= API: Tasks =========================
 * Purpose: Handle CRUD operations for tasks.
 * Table: icmis_tasks
 * ============================================================================ 
 */

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
header('Content-Type: application/json');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8mb4");

try {
    // -------------------- DELETE TASK --------------------
    if (isset($_POST['delete_id'])) {
        $task_id = intval($_POST['delete_id']);

        $stmt = $conn->prepare("DELETE FROM icmis_tasks WHERE task_id = ?");
        $stmt->bind_param("i", $task_id);
        $stmt->execute();

        echo json_encode([
            'success' => $stmt->affected_rows > 0,
            'message' => $stmt->affected_rows > 0 ? 'Task deleted successfully' : 'Task not found'
        ]);
        $stmt->close();
        exit;
    }

    // -------------------- GET NEXT TASK ID --------------------
    if (isset($_GET['get_next_id'])) {
        $result = $conn->query("SELECT task_id FROM icmis_tasks ORDER BY task_id DESC LIMIT 1");
        $row = $result->fetch_assoc();
        $nextId = ($row ? $row['task_id'] : 0) + 1;

        echo json_encode(['success' => true, 'next_id' => $nextId]);
        exit;
    }

    // -------------------- FETCH SINGLE TASK --------------------
    if (isset($_GET['fetch_id'])) {
        $task_id = intval($_GET['fetch_id']);
        
        $stmt = $conn->prepare("
            SELECT t.*, p.project_name, ph.phase_name,
                   CONCAT(e.first_name, ' ', e.last_name) as assigned_to_name
            FROM icmis_tasks t
            LEFT JOIN icmis_projects p ON t.project_id = p.project_id
            LEFT JOIN icmis_project_phases ph ON t.phase_id = ph.phase_id
            LEFT JOIN workforce_employees e ON t.assigned_to_employee_id = e.employee_id
            WHERE t.task_id = ? 
            LIMIT 1
        ");
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $task = $result->fetch_assoc();
        $stmt->close();

        echo json_encode([
            'success' => (bool)$task,
            'record' => $task ?? null
        ]);
        exit;
    }

    // -------------------- ADD OR UPDATE TASK --------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
        $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $task_name = trim($_POST['task_name'] ?? '');
        $project_id = !empty($_POST['project_id']) ? intval($_POST['project_id']) : null;
        $phase_id = !empty($_POST['phase_id']) ? intval($_POST['phase_id']) : null;
        $description = trim($_POST['description'] ?? '');
        $assigned_to = !empty($_POST['assigned_to_employee_id']) ? intval($_POST['assigned_to_employee_id']) : null;
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $priority = $_POST['priority'] ?? 'Medium';
        $status = $_POST['status'] ?? 'Not Started';

        // Check if Task ID exists
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM icmis_tasks WHERE task_id = ?");
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc()['cnt'] > 0;
        $stmt->close();

        if ($exists) {
            // UPDATE
            $sql = "UPDATE icmis_tasks SET 
                        task_name = ?,
                        project_id = ?,
                        phase_id = ?,
                        description = ?,
                        assigned_to_employee_id = ?,
                        start_date = ?,
                        due_date = ?,
                        priority = ?,
                        status = ?
                    WHERE task_id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siisissssi", $task_name, $project_id, $phase_id, $description, $assigned_to, $start_date, $due_date, $priority, $status, $task_id);
            $msg = "Task updated successfully";
        } else {
            // INSERT
            $sql = "INSERT INTO icmis_tasks 
                    (task_name, project_id, phase_id, description, assigned_to_employee_id, start_date, due_date, priority, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siisisss", $task_name, $project_id, $phase_id, $description, $assigned_to, $start_date, $due_date, $priority, $status);
            $msg = "Task added successfully";
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    // -------------------- FETCH ALL TASKS --------------------
    $sql = "SELECT t.*, p.project_name, ph.phase_name,
                   CONCAT(e.first_name, ' ', e.last_name) as assigned_to_name
            FROM icmis_tasks t
            LEFT JOIN icmis_projects p ON t.project_id = p.project_id
            LEFT JOIN icmis_project_phases ph ON t.phase_id = ph.phase_id
            LEFT JOIN workforce_employees e ON t.assigned_to_employee_id = e.employee_id
            ORDER BY t.due_date ASC";
    $result = $conn->query($sql);
    
    $tasks = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'tasks' => $tasks
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
