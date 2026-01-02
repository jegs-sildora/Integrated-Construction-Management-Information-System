<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../connection.php';

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Expense ID is required']);
    exit;
}

$expense_id = intval($_GET['id']);

try {
    // Fetch expense details with related data
    $sql = "SELECT e.*, s.name as supplier_name, s.phone, s.email, s.address,
            p.name as project_name, p.project_code
            FROM budget_expenses e
            LEFT JOIN budget_suppliers s ON e.supplier_id = s.supplier_id
            LEFT JOIN projects p ON e.project_id = p.project_id
            WHERE e.expense_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $expense_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Expense not found']);
        exit;
    }
    
    $expense = $result->fetch_assoc();
    
    // Format dates
    $expense['formatted_date'] = date('F j, Y', strtotime($expense['expense_date']));
    $expense['formatted_created'] = date('F j, Y \a\t g:i A', strtotime($expense['created_at']));
    $expense['formatted_updated'] = date('F j, Y \a\t g:i A', strtotime($expense['updated_at']));
    
    echo json_encode([
        'success' => true,
        'expense' => $expense
    ]);
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
