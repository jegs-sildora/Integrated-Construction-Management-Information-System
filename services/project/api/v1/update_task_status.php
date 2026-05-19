<?php
error_reporting(0);
ini_set('display_errors', 0);
/**
 * ========================= API: Update Task Status =========================
 * Purpose: Handle task status updates (e.g., for Kanban drag-and-drop).
 * Table: tasks
 * ============================================================================ 
 */

require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../Logger.php';
header('Content-Type: application/json');

$db = \Database::getConnection();

try {
    $method = $_SERVER['REQUEST_METHOD'];

    // Handle JSON Input from API Gateway
    if (empty($_POST)) {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        if (is_array($input)) {
            $_POST = $input;
        }
    }

    if ($method !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';

    if ($task_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
        exit;
    }

    // Valid statuses from database ENUM
    $validStatuses = ['Not Started', 'In Progress', 'On Hold', 'Completed'];

    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        exit;
    }

    // Get task name for logging
    $stmtTask = $db->prepare("SELECT task_name FROM tasks WHERE task_id = ?");
    $stmtTask->execute([$task_id]);
    $taskName = $stmtTask->fetchColumn();

    $stmt = $db->prepare("UPDATE tasks SET status = ? WHERE task_id = ?");
    $stmt->execute([$status, $task_id]);

    if ($stmt->rowCount() > 0) {
        Logger::update('Project', "Updated task status: $taskName to $status", $task_id);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Task status updated successfully',
            'task_id' => $task_id,
            'new_status' => $status
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Task not found or status unchanged'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

