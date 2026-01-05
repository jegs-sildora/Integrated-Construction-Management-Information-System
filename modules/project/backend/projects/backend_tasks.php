<?php
/**
 * ========================= BACKEND: Project Tasks =========================
 * Purpose: Handle CRUD operations for tasks (Add, Edit, Delete, Fetch).
 * Table: tasks
 * ============================================================================ 
 */

require '../config.php'; // Ensure this path points to your actual database connection
header('Content-Type: application/json');

try {
    // ==========================================================
    // 1. DELETE TASK
    // Triggered by: $.post(backendUrl, { delete_id: id })
    // ==========================================================
    if (isset($_POST['delete_id'])) {
        $task_id = $_POST['delete_id'];

        $stmt = $conn->prepare("DELETE FROM tasks WHERE task_id = :task_id");
        $stmt->execute([':task_id' => $task_id]);

        echo json_encode([
            'success' => $stmt->rowCount() > 0,
            'message' => $stmt->rowCount() > 0 ? 'Task deleted successfully' : 'Task not found or already deleted'
        ]);
        exit;
    }

    // ==========================================================
    // 2. GET NEXT TASK ID
    // Triggered by: $.getJSON(backendUrl, { get_next_id: 1 })
    // ==========================================================
    if (isset($_GET['get_next_id'])) {
        $stmt = $conn->prepare("SELECT task_id FROM tasks ORDER BY task_id DESC LIMIT 1");
        $stmt->execute();
        $lastId = $stmt->fetch(PDO::FETCH_ASSOC)['task_id'] ?? null;

        if ($lastId) {
            // Remove non-numeric characters, increment, repad
            $num = intval(preg_replace('/[^0-9]/', '', $lastId)) + 1;
        } else {
            $num = 1;
        }
        
        $nextId = 'TSK' . str_pad($num, 3, '0', STR_PAD_LEFT);

        echo json_encode(['success' => true, 'next_id' => $nextId]);
        exit;
    }

    // ==========================================================
    // 3. FETCH SINGLE TASK (FOR EDIT MODAL)
    // Triggered by: $.getJSON(backendUrl, { fetch_id: id })
    // ==========================================================
    if (isset($_GET['fetch_id'])) {
        $task_id = $_GET['fetch_id'];
        
        // Simple select to get raw data for the form
        $stmt = $conn->prepare("SELECT * FROM tasks WHERE task_id = ? LIMIT 1");
        $stmt->execute([$task_id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => (bool)$task,
            'record' => $task ?? null
        ]);
        exit;
    }

    // ==========================================================
    // 4. ADD OR UPDATE TASK
    // Triggered by: $("#taskForm").submit()
    // ==========================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
        
        // Collect Inputs
        $task_id         = $_POST['task_id'] ?? '';
        $task_name       = $_POST['task_name'] ?? '';
        $project_id      = !empty($_POST['project_id']) ? $_POST['project_id'] : null;
        $phase_id        = !empty($_POST['phase_id']) ? $_POST['phase_id'] : null;
        $description     = $_POST['description'] ?? '';
        $assigned_to     = $_POST['assigned_to'] ?? '';
        $due_date        = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $priority        = $_POST['priority'] ?? 'Medium';
        $status          = $_POST['status'] ?? 'Not Started';
        $estimated_hours = !empty($_POST['estimated_hours']) ? $_POST['estimated_hours'] : 0.00;
        $department      = $_POST['department'] ?? '';
        $is_active       = 1;

        // Check if Task ID exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE task_id = ?");
        $stmt->execute([$task_id]);
        $exists = $stmt->fetchColumn() > 0;

        if ($exists) {
            // --- UPDATE ---
            $sql = "UPDATE tasks SET 
                        task_name = :task_name,
                        project_id = :project_id,
                        phase_id = :phase_id,
                        description = :description,
                        assigned_to = :assigned_to,
                        due_date = :due_date,
                        priority = :priority,
                        status = :status,
                        estimated_hours = :estimated_hours,
                        department = :department
                    WHERE task_id = :task_id";
            
            $msg = "Task updated successfully";
        } else {
            // --- INSERT ---
            $sql = "INSERT INTO tasks 
                    (task_id, task_name, project_id, phase_id, description, assigned_to, due_date, priority, status, estimated_hours, department, is_active)
                    VALUES 
                    (:task_id, :task_name, :project_id, :phase_id, :description, :assigned_to, :due_date, :priority, :status, :estimated_hours, :department, :is_active)";
            
            $msg = "Task added successfully";
        }

        $stmt = $conn->prepare($sql);

        $params = [
            ':task_id'        => $task_id,
            ':task_name'      => $task_name,
            ':project_id'     => $project_id,
            ':phase_id'       => $phase_id,
            ':description'    => $description,
            ':assigned_to'    => $assigned_to,
            ':due_date'       => $due_date,
            ':priority'       => $priority,
            ':status'         => $status,
            ':estimated_hours'=> $estimated_hours,
            ':department'     => $department
        ];

        if (!$exists) {
            $params[':is_active'] = $is_active;
        }

        if ($stmt->execute($params)) {
            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            $err = $stmt->errorInfo();
            echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $err[2]]);
        }
        exit;
    }

    // ==========================================================
    // 5. FETCH ALL TASKS (For Table Display)
    // Triggered by: loadTasks()
    // ==========================================================
    // Using LEFT JOIN to ensure task shows even if project/phase was deleted
    $sql = "SELECT 
                t.*,
                p.project_name,
                ph.phase_name
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.project_id
            LEFT JOIN project_phases ph ON t.phase_id = ph.phase_id
            ORDER BY t.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'tasks' => $tasks
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>