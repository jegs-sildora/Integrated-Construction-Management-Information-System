<?php
/**
 * ========================= API: Tasks =========================
 * Purpose: Handle CRUD operations for tasks.
 * Table: tasks
 * ============================================================================ 
 */

require_once __DIR__ . '/../../Database.php';
header('Content-Type: application/json');

$db = Database::getConnection();

try {
    $method = $_SERVER['REQUEST_METHOD'];

    // Handle JSON Input from API Gateway
    if (empty($_POST)) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (is_array($input)) {
            $_POST = $input;
        }
    }

    // -------------------- DELETE TASK --------------------
    if ($method === 'POST' && isset($_POST['delete_id'])) {
        $task_id = intval($_POST['delete_id']);

        $stmt = $db->prepare("DELETE FROM tasks WHERE task_id = ?");
        $stmt->execute([$task_id]);

        echo json_encode([
            'success' => $stmt->rowCount() > 0,
            'message' => $stmt->rowCount() > 0 ? 'Task deleted successfully' : 'Task not found'
        ]);
        exit;
    }

    // -------------------- GET NEXT TASK ID --------------------
    if ($method === 'GET' && isset($_GET['get_next_id'])) {
        $stmt = $db->query("SELECT task_id FROM tasks ORDER BY task_id DESC LIMIT 1");
        $row = $stmt->fetch();
        $nextId = ($row ? $row['task_id'] : 0) + 1;

        echo json_encode(['success' => true, 'next_id' => $nextId]);
        exit;
    }

    // -------------------- FETCH SINGLE TASK --------------------
    if ($method === 'GET' && isset($_GET['fetch_id'])) {
        $task_id = intval($_GET['fetch_id']);
        
        $stmt = $db->prepare("
            SELECT t.*, p.project_name, ph.phase_name
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.project_id
            LEFT JOIN project_phases ph ON t.phase_id = ph.phase_id
            WHERE t.task_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$task_id]);
        $task = $stmt->fetch();

        echo json_encode([
            'success' => (bool)$task,
            'record' => $task ?? null
        ]);
        exit;
    }

    // -------------------- ADD OR UPDATE TASK --------------------
    if ($method === 'POST' && !isset($_POST['delete_id'])) {
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

        // Basic validation for enums (based on DB schema)
        $validStatuses = ['Not Started', 'In Progress', 'Completed', 'On Hold'];
        $validPriorities = ['Low', 'Medium', 'High', 'Urgent'];

        if (!in_array($status, $validStatuses)) $status = 'Not Started';
        if (!in_array($priority, $validPriorities)) $priority = 'Medium';

        if ($task_id > 0) {
            // UPDATE
            $sql = "UPDATE tasks SET 
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
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$task_name, $project_id, $phase_id, $description, $assigned_to, $start_date, $due_date, $priority, $status, $task_id]);
            $msg = "Task updated successfully";
        } else {
            // INSERT
            $sql = "INSERT INTO tasks 
                    (task_name, project_id, phase_id, description, assigned_to_employee_id, start_date, due_date, priority, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$task_name, $project_id, $phase_id, $description, $assigned_to, $start_date, $due_date, $priority, $status]);
            $msg = "Task added successfully";
        }

        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }

    // -------------------- FETCH ALL TASKS --------------------
    $stmt = $db->query("
        SELECT t.*, p.project_name, ph.phase_name
        FROM tasks t
        LEFT JOIN projects p ON t.project_id = p.project_id
        LEFT JOIN project_phases ph ON t.phase_id = ph.phase_id
        ORDER BY t.due_date ASC
    ");
    $tasks = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'tasks' => $tasks
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
