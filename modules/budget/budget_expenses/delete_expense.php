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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Read JSON from request body
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

$expense_id = isset($data['expense_id']) ? intval($data['expense_id']) : 0;

if ($expense_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid expense ID']);
    exit;
}

try {
    $conn->begin_transaction();
    
    // Check if expense exists
    $sql_check = "SELECT expense_id FROM budget_expenses WHERE expense_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $expense_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Expense not found');
    }
    $stmt_check->close();
    
    // Delete expense
    $sql_delete = "DELETE FROM budget_expenses WHERE expense_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $expense_id);
    
    if (!$stmt_delete->execute()) {
        throw new Exception('Failed to delete expense');
    }
    
    $stmt_delete->close();
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Expense deleted successfully'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
