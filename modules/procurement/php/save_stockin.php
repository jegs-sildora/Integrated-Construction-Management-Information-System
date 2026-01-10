<?php
// modules/procurement/php/save_stockin.php
header('Content-Type: application/json');

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/Logger.php';

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
    // Fetch project_id and phase_id from the PO first
    $po_project_id = null;
    $po_phase_id = null;
    $stmt_po = $conn->prepare("SELECT po.project_id, po.phase_id FROM procurement_purchase_orders po WHERE po.po_id = ?");
    $stmt_po->bind_param('i', $po_id);
    $stmt_po->execute();
    $res_po = $stmt_po->get_result();
    if ($row_po = $res_po->fetch_assoc()) {
        $po_project_id = intval($row_po['project_id']);
        $po_phase_id = isset($row_po['phase_id']) ? intval($row_po['phase_id']) : null;
    }
    $stmt_po->close();

    // Helper to get unit cost, item name and referenced inventory id from original PO item
    $stmt_details = $conn->prepare("SELECT unit_cost, item_name, inventory_item_id FROM procurement_purchase_order_items WHERE po_item_id = ?");

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

            // Determine inventory item_id: prefer referenced inventory_item_id, otherwise find/create by name
            $inv_item_id = isset($row_details['inventory_item_id']) && intval($row_details['inventory_item_id']) > 0 ? intval($row_details['inventory_item_id']) : null;

            if (!$inv_item_id) {
                // Try to find existing inventory by name and project
                $stmt_check_inv = $conn->prepare("SELECT item_id, quantity FROM procurement_inventory WHERE item_name = ? AND project_id = ? LIMIT 1");
                $stmt_check_inv->bind_param('si', $name, $po_project_id);
                $stmt_check_inv->execute();
                $res_check_inv = $stmt_check_inv->get_result();
                if ($row_inv = $res_check_inv->fetch_assoc()) {
                    $inv_item_id = intval($row_inv['item_id']);
                }
                $stmt_check_inv->close();
            }

            // If still no inventory item, create one (quantity will be set after inserting stock_in)
            if (!$inv_item_id) {
                $stmt_ins_inv = $conn->prepare("INSERT INTO procurement_inventory (item_name, quantity, unit_cost, unit, category, project_id, phase_id, last_updated) VALUES (?, 0, ?, 'pcs', 'General', ?, ?, NOW())");
                $stmt_ins_inv->bind_param('sdii', $name, $cost, $po_project_id, $po_phase_id);
                if (!$stmt_ins_inv->execute()) throw new Exception("Failed to insert inventory: " . $stmt_ins_inv->error);
                $inv_item_id = $stmt_ins_inv->insert_id;
                $stmt_ins_inv->close();
            }

            // Insert into procurement_stock_in using item_id
            $total_cost = floatval($cost) * floatval($qty);
            $stmt_stockin = $conn->prepare("INSERT INTO procurement_stock_in (po_id, item_id, quantity_received, unit_cost, total_cost, date_received) VALUES (?, ?, ?, ?, ?, CURDATE())");
            $stmt_stockin->bind_param("iiidd", $po_id, $inv_item_id, $qty, $cost, $total_cost);
            if (!$stmt_stockin->execute()) throw new Exception("Failed to log stock in: " . $stmt_stockin->error);
            $stmt_stockin->close();

            // Update Master Inventory (UPSERT) - increase quantity and set unit cost
            $stmt_upd_inv = $conn->prepare("UPDATE procurement_inventory SET quantity = quantity + ?, unit_cost = ?, last_updated = NOW() WHERE item_id = ?");
            $stmt_upd_inv->bind_param('dii', $qty, $cost, $inv_item_id);
            if (!$stmt_upd_inv->execute()) throw new Exception("Failed to update inventory: " . $stmt_upd_inv->error);
            $stmt_upd_inv->close();
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
        $po_stmt = $conn->prepare("SELECT project_id, phase_id, supplier_id, total_amount, order_title, po_reference FROM procurement_purchase_orders WHERE po_id = ?");
        $po_stmt->bind_param("i", $po_id);
        $po_stmt->execute();
        $po_result = $po_stmt->get_result();
        
        if ($po_data = $po_result->fetch_assoc()) {
            
            // C. Insert into Budget Expenses (Same database now)
            $description = $po_data['po_reference'] . " - " . ($po_data['order_title'] ?? 'Purchase Order');
            $project_id = intval($po_data['project_id']);
            $phase_id = isset($po_data['phase_id']) ? intval($po_data['phase_id']) : null;
            $supplier_id = $po_data['supplier_id'];
            $amount = $po_data['total_amount'];
            
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

    // Log the audit trail
    Logger::init($conn);
    Logger::create('Procurement', "Stock Received for PO #$po_id - Items processed successfully", $po_id);

    sendJson(true, 'Stock received successfully!');

} catch (Exception $e) {
    $conn->rollback();
    sendJson(false, $e->getMessage());
}

$conn->close();
?>