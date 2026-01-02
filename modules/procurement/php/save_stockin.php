<?php
// modules/inventory/php/save_stockin.php
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

$db = $conn_proc ?? $conn;

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
$received_by = intval($data['received_by'] ?? 1);
$items = $data['items'] ?? [];

if ($po_id <= 0 || empty($items)) sendJson(false, 'Missing PO ID or Items');

$db->begin_transaction();

try {
    // 1. Prepare Inventory & Log Statements
    $stmt_log = $db->prepare("INSERT INTO inventory_receiving_logs (po_id, item_id_ref, item_name, quantity_received, received_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");

    // UPSERT: Update inventory if exists, Insert if new
    $stmt_inventory = $db->prepare("INSERT INTO inventory (item_name, quantity, unit_cost, unit, category) 
        VALUES (?, ?, ?, ?, 'General') 
        ON DUPLICATE KEY UPDATE 
        quantity = quantity + VALUES(quantity), 
        unit_cost = VALUES(unit_cost),
        unit = VALUES(unit),
        last_updated = NOW()");

    // Helper to get unit cost from original PO item
    $stmt_details = $db->prepare("SELECT unit_cost FROM purchase_order_items WHERE id = ?");

    // 2. Process Each Item
    foreach ($items as $item) {
        $po_item_id = intval($item['item_db_id']);
        $name = trim($item['item_name']);
        $qty = floatval($item['received_qty']);

        if ($qty > 0) {
            // Fetch Cost
            $stmt_details->bind_param("i", $po_item_id);
            $stmt_details->execute();
            $res_details = $stmt_details->get_result();
            $row_details = $res_details->fetch_assoc();
            $cost = $row_details['unit_cost'] ?? 0;
            $unit = 'pcs'; // Default unit

            // Insert Log
            $stmt_log->bind_param("iisdi", $po_id, $po_item_id, $name, $qty, $received_by);
            if (!$stmt_log->execute()) throw new Exception("Failed to log item: " . $stmt_log->error);

            // Update Master Inventory
            $stmt_inventory->bind_param("sdds", $name, $qty, $cost, $unit);
            if (!$stmt_inventory->execute()) throw new Exception("Failed to update inventory: " . $stmt_inventory->error);
        }
    }

    // 3. Check for PO Completion
    // Compare total ordered vs total received
    $sql_check = "
        SELECT 
            (SELECT SUM(quantity) FROM purchase_order_items WHERE po_id = ?) as total_ordered,
            (SELECT SUM(quantity_received) FROM inventory_receiving_logs WHERE po_id = ?) as total_received
    ";
    $stmt_check = $db->prepare($sql_check);
    $stmt_check->bind_param("ii", $po_id, $po_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    $status_row = $res_check->fetch_assoc();

    // If Received >= Ordered, mark as COMPLETED and Push to Expenses
    if ($status_row['total_received'] >= $status_row['total_ordered']) {
        
        // A. Update PO Status
        $db->query("UPDATE purchase_orders SET status = 'COMPLETED' WHERE po_id = $po_id");

        // B. Fetch PO Data required for Expenses
        $po_stmt = $db->prepare("SELECT project_id, phase, supplier_id, total_amount, order_title, po_reference FROM purchase_orders WHERE po_id = ?");
        $po_stmt->bind_param("i", $po_id);
        $po_stmt->execute();
        $po_result = $po_stmt->get_result();
        
        if ($po_data = $po_result->fetch_assoc()) {
            
            // C. Insert into Budget Expenses (Cross-Database Insert)
            // We use 'icmis_budget' prefix to target the other database
            
            $description = $po_data['po_reference'] . " - " . $po_data['order_title'];
            $project_id = $po_data['project_id'];
            $phase = $po_data['phase'];
            $supplier_id = $po_data['supplier_id']; // Assumes supplier IDs are synced or shared
            $amount = $po_data['total_amount'];
            
            $expense_sql = "INSERT INTO icmis_budget.budget_expenses 
                            (project_id, phase, category, description, supplier_id, amount, status, expense_date, created_at, updated_at) 
                            VALUES (?, ?, 'MATERIALS', ?, ?, ?, 'APPROVED', NOW(), NOW(), NOW())";
            
            $stmt_exp = $db->prepare($expense_sql);
            
            if ($stmt_exp) {
                $stmt_exp->bind_param("issid", $project_id, $phase, $description, $supplier_id, $amount);
                if (!$stmt_exp->execute()) {
                    throw new Exception("Stock received, but failed to record Expense: " . $stmt_exp->error);
                }
                $stmt_exp->close();
            } else {
                // If icmis_budget database doesn't exist or permissions denied
                throw new Exception("Failed to access Budget Database. Please check configuration.");
            }
        }
        $po_stmt->close();
    }

    $db->commit();
    sendJson(true, 'Stock received successfully!');

} catch (Exception $e) {
    $db->rollback();
    sendJson(false, $e->getMessage());
}
?>