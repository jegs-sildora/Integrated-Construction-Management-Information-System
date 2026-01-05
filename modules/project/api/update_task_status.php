<?php
// modules/project/api/update_task_status.php
// API endpoint for updating task status via drag-and-drop in Kanban board

header('Content-Type: application/json');

// 1. Configuration
require_once __DIR__ . '/../../../config/config.php';

// 2. Authentication Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// 3. Database Connection
require_once __DIR__ . '/../../../config/database.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

// Validate inputs
if ($task_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
    exit();
}

// Valid statuses from database ENUM
$validStatuses = ['Not Started', 'In Progress', 'On Hold', 'Completed'];

if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit();
}

// Update the task status
$stmt = $conn->prepare("UPDATE icmis_tasks SET status = ? WHERE task_id = ?");
$stmt->bind_param("si", $status, $task_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
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
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
