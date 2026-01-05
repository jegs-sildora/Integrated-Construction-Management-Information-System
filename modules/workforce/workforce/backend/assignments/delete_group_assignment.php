<?php
require '../config.php';
header('Content-Type: application/json');

$group_assignment_id = $_POST['group_assignment_id'] ?? null;

if (!$group_assignment_id) {
    echo json_encode(['success' => false, 'message' => 'Assignment ID required']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM group_assignments WHERE group_assignment_id = :group_assignment_id");
    $stmt->execute(['group_assignment_id' => $group_assignment_id]);

    echo json_encode(['success' => true, 'message' => 'Group assignment deleted successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
