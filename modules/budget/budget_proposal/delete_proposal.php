<?php
header('Content-Type: application/json');

// Include config for database connection
require_once __DIR__ . '/../../config/config.php';

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset("utf8mb4");

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!isset($data['proposal_id']) || !is_numeric($data['proposal_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid proposal ID'
    ]);
    exit;
}

$proposal_id = intval($data['proposal_id']);

// Start transaction
$conn->begin_transaction();

try {
    // First, check if the proposal exists
    $check_sql = "SELECT proposal_id, code, status FROM budget_proposals WHERE proposal_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $proposal_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Proposal not found');
    }
    
    $proposal = $result->fetch_assoc();
    $check_stmt->close();
    
    // Optional: Prevent deletion of approved proposals
    // Uncomment the following lines if you want to restrict deletion of approved proposals
    /*
    if ($proposal['status'] === 'APPROVED') {
        throw new Exception('Cannot delete approved proposals');
    }
    */
    
    // Delete line items first (due to foreign key constraint)
    $delete_items_sql = "DELETE FROM budget_line_items WHERE proposal_id = ?";
    $delete_items_stmt = $conn->prepare($delete_items_sql);
    $delete_items_stmt->bind_param("i", $proposal_id);
    
    if (!$delete_items_stmt->execute()) {
        throw new Exception('Failed to delete proposal line items');
    }
    $delete_items_stmt->close();
    
    // Delete the proposal
    $delete_proposal_sql = "DELETE FROM budget_proposals WHERE proposal_id = ?";
    $delete_proposal_stmt = $conn->prepare($delete_proposal_sql);
    $delete_proposal_stmt->bind_param("i", $proposal_id);
    
    if (!$delete_proposal_stmt->execute()) {
        throw new Exception('Failed to delete proposal');
    }
    $delete_proposal_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Proposal ' . $proposal['code'] . ' deleted successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>