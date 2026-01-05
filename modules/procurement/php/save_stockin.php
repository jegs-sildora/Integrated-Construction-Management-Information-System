<?php
// modules/procurement/php/save_stockin.php
header('Content-Type: application/json');

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8mb4");

function sendJson($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(false, 'Invalid request method');
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) sendJson(false, 'Invalid JSON data');

$po_id = intval($data['po_id'] ?? 0);
$items = $data['items'] ?? [];

if ($po_id <= 0 || empty($items)) sendJson(false, 'Missing PO ID or Items');

$conn->begin_transaction();

try {
    // Helper to get unit cost from original PO item
    $stmt_details = $conn->prepare("SELECT unit_cost, item_name FROM procurement_purchase_order_items WHERE po_item_id = ?");

    // 1. Process Each Item - Insert into stock_in and update inventory
    foreach ($items as $item) {
        $po_item_id = intval($item['item_db_id']);
        $name = trim($item['item_name']);
        $qty = intval($item['received_qty']);

        if ($qty > 0) {
            // Fetch Cost
            $stmt_details->bind_param("i", $po_item_id);
            $stmt_details->execute();
            $res_details = $stmt_details->get_result();
            $row_details = $res_details->fetch_assoc();
            $cost = $row_details['unit_cost'] ?? 0;

            // Insert into procurement_stock_in
            $stmt_stockin = $conn->prepare("INSERT INTO procurement_stock_in (po_id, item_name, quantity_received, date_received) VALUES (?, ?, ?, CURDATE())");
            $stmt_stockin->bind_param("isi", $po_id, $name, $qty);
            if (!$stmt_stockin->execute()) throw new Exception("Failed to log stock in: " . $stmt_stockin->error);
            $stmt_stockin->close();

            // Update Master Inventory (UPSERT)
            $stmt_inventory = $conn->prepare("INSERT INTO procurement_inventory (item_name, quantity, unit_cost, unit, category) 
                VALUES (?, ?, ?, 'pcs', 'General') 
                ON DUPLICATE KEY UPDATE 
                quantity = quantity + VALUES(quantity), 
                unit_cost = VALUES(unit_cost),
                last_updated = NOW()");
            $stmt_inventory->bind_param("sdd", $name, $qty, $cost);
            if (!$stmt_inventory->execute()) throw new Exception("Failed to update inventory: " . $stmt_inventory->error);
            $stmt_inventory->close();
        }
    }
    $stmt_details->close();

    // 2. Check for PO Completion - Compare total ordered vs total received
    $sql_check = "
        SELECT 
            (SELECT SUM(quantity) FROM procurement_purchase_order_items WHERE po_id = ?) as total_ordered,
            (SELECT SUM(quantity_received) FROM procurement_stock_in WHERE po_id = ?) as total_received
    ";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $po_id, $po_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    $status_row = $res_check->fetch_assoc();
    $stmt_check->close();

    // If Received >= Ordered, mark as COMPLETED and Push to Expenses
    if ($status_row['total_received'] >= $status_row['total_ordered']) {
        
        // A. Update PO Status
        $conn->query("UPDATE procurement_purchase_orders SET status = 'COMPLETED' WHERE po_id = $po_id");

        // B. Fetch PO Data required for Expenses
        $po_stmt = $conn->prepare("SELECT project_id, phase, supplier_id, total_amount, order_title, po_reference FROM procurement_purchase_orders WHERE po_id = ?");
        $po_stmt->bind_param("i", $po_id);
        $po_stmt->execute();
        $po_result = $po_stmt->get_result();
        
        if ($po_data = $po_result->fetch_assoc()) {
            
            // C. Insert into Budget Expenses (Same database now)
            $description = $po_data['po_reference'] . " - " . ($po_data['order_title'] ?? 'Purchase Order');
            $project_id = $po_data['project_id'];
            $phase = $po_data['phase'];
            $supplier_id = $po_data['supplier_id'];
            $amount = $po_data['total_amount'];
            
            // Look up phase_id from phase name
            $phase_id = null;
            $stmt_phase = $conn->prepare("SELECT phase_id FROM icmis_project_phases WHERE project_id = ? AND phase_name LIKE ?");
            $phase_search = '%' . $phase . '%';
            $stmt_phase->bind_param("is", $project_id, $phase_search);
            $stmt_phase->execute();
            $result_phase = $stmt_phase->get_result();
            if ($row_phase = $result_phase->fetch_assoc()) {
                $phase_id = $row_phase['phase_id'];
            }
            $stmt_phase->close();
            
            $expense_sql = "INSERT INTO budget_expenses 
                            (project_id, phase_id, category, description, supplier_id, amount, status, expense_date) 
                            VALUES (?, ?, 'MATERIALS', ?, ?, ?, 'APPROVED', CURDATE())";
            
            $stmt_exp = $conn->prepare($expense_sql);
            $stmt_exp->bind_param("iisid", $project_id, $phase_id, $description, $supplier_id, $amount);
            if (!$stmt_exp->execute()) {
                throw new Exception("Stock received, but failed to record Expense: " . $stmt_exp->error);
            }
            $stmt_exp->close();
        }
        $po_stmt->close();
    }

    $conn->commit();
    sendJson(true, 'Stock received successfully!');

} catch (Exception $e) {
    $conn->rollback();
    sendJson(false, $e->getMessage());
}

$conn->close();
?>