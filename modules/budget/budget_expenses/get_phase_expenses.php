<?php
/**
 * Get Phase Expenses - Now sourced from Procurement Purchase Orders
 * 
 * This file has been refactored to fetch expense data from completed 
 * Purchase Orders in the Procurement module instead of the budget_expenses table.
 * 
 * Filtering Logic:
 * - Only fetch Purchase Orders with status = 'COMPLETED' (approved and delivered)
 * 
 * Column Mapping from Procurement to Budget:
 * - po_id -> expense_id (for compatibility)
 * - order_date -> expense_date
 * - phase -> phase
 * - 'MATERIALS' -> category (default, derived from PO context)
 * - order_title + item details -> description
 * - total_amount -> amount
 * - 'APPROVED' -> status (completed POs are considered approved expenses)
 * - supplierName -> supplier_name
 */

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

try {
    // Validate inputs
    if (!isset($_GET['project_id']) || !isset($_GET['phase'])) {
        throw new Exception('Missing required parameters');
    }

    $project_id = intval($_GET['project_id']);
    $phase_name = trim($_GET['phase']);

    // Fetch completed Purchase Orders for the phase
    // We're sourcing expense data from the Procurement module's purchase_orders table
    // Only POs with status = 'COMPLETED' are considered actual expenses
    $sql = "SELECT 
                po.po_id as expense_id,
                po.po_id,
                po.po_reference,
                po.order_date as expense_date,
                po.phase,
                'MATERIALS' as category,
                CONCAT(po.order_title, ' (', po.po_reference, ')') as description,
                po.total_amount as amount,
                'APPROVED' as status,
                s.supplierName as supplier_name
            FROM purchase_orders po
            LEFT JOIN suppliers s ON po.supplier_id = s.supplierID
            WHERE po.project_id = ? 
              AND po.phase = ?
              AND po.status = 'COMPLETED'
            ORDER BY po.order_date DESC, po.po_id DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }
    
    $stmt->bind_param("is", $project_id, $phase_name);
    $stmt->execute();
    $result = $stmt->get_result();

    $expenses = [];
    while ($row = $result->fetch_assoc()) {
        $expenses[] = [
            'expense_id' => $row['expense_id'],
            'po_id' => $row['po_id'],
            'po_reference' => $row['po_reference'],
            'expense_date' => $row['expense_date'],
            'category' => $row['category'],
            'description' => $row['description'],
            'amount' => floatval($row['amount']),
            'status' => $row['status'],
            'supplier_name' => $row['supplier_name'],
            'phase' => $row['phase']
        ];
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'expenses' => $expenses,
        'count' => count($expenses),
        'source' => 'procurement_purchase_orders'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
