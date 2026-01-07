<?php
require_once '../config.php'; // PDO connection
header('Content-Type: application/json');

$assignment_id = $_POST['assignment_id'] ?? null;

if (!$assignment_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Assignment ID is required.'
    ]);
    exit;
}

try {
    $sql = "
        SELECT 
            a.assignment_id,
            a.employee_id,
            a.project_id,
            a.phase_id,
            a.role,
            a.task_description AS task_name,
            a.start_date,
            a.end_date,
            a.status,
            a.notes
        FROM assignments a
        WHERE a.assignment_id = :assignment_id
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute(['assignment_id' => $assignment_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($assignment) {
        echo json_encode([
            'success' => true,
            'assignment' => $assignment
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Assignment not found.'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
