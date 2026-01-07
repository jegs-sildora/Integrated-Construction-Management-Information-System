<?php
header('Content-Type: application/json');

// Include config for database connection
require_once __DIR__ . '/../../../config/config.php';

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset("utf8mb4");

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Expense ID is required']);
    exit;
}

$expense_id = intval($_GET['id']);

try {
    // First try: treat id as a Procurement PO (po_id)
    $sql_po = "SELECT po.po_id, po.po_reference, po.order_date, po.total_amount, po.status, po.order_title,
                      po.phase_id, po.supplier_id, p.project_name, p.project_code,
                      s.supplier_name, s.contact_number as phone, s.email, s.address, pp.phase_name
               FROM procurement_purchase_orders po
               LEFT JOIN procurement_suppliers s ON po.supplier_id = s.supplier_id
               LEFT JOIN icmis_projects p ON po.project_id = p.project_id
               LEFT JOIN icmis_project_phases pp ON po.phase_id = pp.phase_id
               WHERE po.po_id = ? LIMIT 1";

    $stmt = $conn->prepare($sql_po);
    $stmt->bind_param("i", $expense_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $po = $result->fetch_assoc();

        $expense = [
            'expense_id' => $po['po_id'],
            'po_id' => $po['po_id'],
            'po_reference' => $po['po_reference'],
            'expense_date' => $po['order_date'],
            'phase' => $po['phase_name'] ?? 'Phase 1: Mobilization',
            'category' => 'MATERIALS',
            'description' => trim(($po['order_title'] ?? '') . ' (' . ($po['po_reference'] ?? '') . ')'),
            'amount' => $po['total_amount'],
            'status' => $po['status'],
            'supplier_name' => $po['supplier_name'],
            'phone' => $po['phone'] ?? null,
            'email' => $po['email'] ?? null,
            'address' => $po['address'] ?? null,
            'project_name' => $po['project_name'] ?? null,
            'project_code' => $po['project_code'] ?? null,
            'notes' => null,
            'receipt_path' => null,
            // procurement_purchase_orders doesn't have created_at/updated_at; use order_date as fallback
            'created_at' => ($po['order_date'] ? $po['order_date'] . ' 00:00:00' : null),
            'updated_at' => ($po['order_date'] ? $po['order_date'] . ' 00:00:00' : null)
        ];

        // Format dates
        $expense['formatted_date'] = $expense['expense_date'] ? date('F j, Y', strtotime($expense['expense_date'])) : '';
        $expense['formatted_created'] = $expense['created_at'] ? date('F j, Y \a\t g:i A', strtotime($expense['created_at'])) : '';
        $expense['formatted_updated'] = $expense['updated_at'] ? date('F j, Y \a\t g:i A', strtotime($expense['updated_at'])) : '';

        echo json_encode(['success' => true, 'expense' => $expense]);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // Fallback: look up in budget_expenses table
    $sql = "SELECT e.*, s.supplier_name, s.contact_number as phone, s.email, s.address,
            p.project_name, p.project_code
            FROM budget_expenses e
            LEFT JOIN procurement_suppliers s ON e.supplier_id = s.supplier_id
            LEFT JOIN icmis_projects p ON e.project_id = p.project_id
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
    
    // Format dates if available
    $expense['formatted_date'] = isset($expense['expense_date']) ? date('F j, Y', strtotime($expense['expense_date'])) : '';
    $expense['formatted_created'] = isset($expense['created_at']) ? date('F j, Y \a\t g:i A', strtotime($expense['created_at'])) : '';
    $expense['formatted_updated'] = isset($expense['updated_at']) ? date('F j, Y \a\t g:i A', strtotime($expense['updated_at'])) : '';
    
    echo json_encode([
        'success' => true,
        'expense' => $expense
    ]);
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
