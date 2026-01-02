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
    $sql = "SELECT e.*, 
            COALESCE(s.name, 'Unknown Supplier') as supplier_name, 
            s.phone, s.email, s.address,
            p.name as project_name, 
            p.project_code
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
    
    // Create line item with actual quantity and unit_cost from database
    $line_items = [[
        'id' => time(), // Use timestamp as unique ID
        'category' => $expense['category'],
        'description' => $expense['description'],
        'quantity' => floatval($expense['quantity'] ?? 1),
        'unitCost' => floatval($expense['unit_cost'] ?? $expense['amount']),
        'subtotal' => floatval($expense['amount'])
    ]];
    
    $expense['line_items'] = $line_items;
    
    echo json_encode([
        'success' => true,
        'expense' => $expense
    ]);
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
