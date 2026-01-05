<?php
require_once '../config.php'; // your PDO connection

header('Content-Type: application/json');

if (!isset($_POST['assignment_id']) || empty($_POST['assignment_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Assignment ID is required.'
    ]);
    exit;
}

$assignment_id = trim($_POST['assignment_id']);

try {
    // Prepare DELETE statement
    $stmt = $conn->prepare("DELETE FROM assignments WHERE assignment_id = :assignment_id");
    $stmt->bindParam(':assignment_id', $assignment_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Assignment deleted successfully.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete assignment.'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
