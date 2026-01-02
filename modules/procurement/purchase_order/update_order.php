<?php
// modules/procurement/purchase_order/update_order.php

// 1. Setup Headers & Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable HTML error output to keep JSON valid

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Connect to Procurement Database
include __DIR__ . '/../php/db_connect.php'; 

// Ensure we have the correct connection variable
$db = $conn_proc ?? $conn; 

if (!isset($db) || $db->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// 3. Process the Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON: ' . json_last_error_msg());
        }

        // --- Validate Required Fields ---
        // We strictly need po_id to know what to update
        if (empty($data['po_id'])) {
            throw new Exception("Missing Purchase Order ID (po_id)");
        }

        $po_id = intval($data['po_id']);
        $project_id = intval($data['project_id']);
        $phase = trim($data['phase']);
        $supplier_input = trim($data['supplier']); 
        $order_title = isset($data['title']) ? trim($data['title']) : 'Untitled Order';
        $status = isset($data['status']) ? trim($data['status']) : 'PENDING';
        $items = $data['items']; // Array of items
        
        if (empty($items)) {
            throw new Exception("Order must contain at least one item.");
        }

        // --- Resolve Supplier ID ---
        $supplier_id = 0;
        if (is_numeric($supplier_input)) {
            $supplier_id = intval($supplier_input);
        } else {
            // Look up ID by Name
            $stmt_sup = $db->prepare("SELECT supplierID FROM suppliers WHERE supplierName = ? LIMIT 1");
            $stmt_sup->bind_param("s", $supplier_input);
            $stmt_sup->execute();
            $res_sup = $stmt_sup->get_result();
            if ($row_sup = $res_sup->fetch_assoc()) {
                $supplier_id = $row_sup['supplierID'];
            } else {
                throw new Exception("Supplier '$supplier_input' not found in database.");
            }
            $stmt_sup->close();
        }

        // --- Calculate New Total Amount ---
        $grand_total = 0;
        foreach ($items as $item) {
            $grand_total += (floatval($item['qty']) * floatval($item['price']));
        }

        // --- Begin Transaction ---
        $db->begin_transaction();

        // A. Update Header Table
        $sql_header = "UPDATE purchase_orders 
                       SET project_id = ?, 
                           supplier_id = ?, 
                           phase = ?, 
                           order_title = ?, 
                           status = ?, 
                           total_amount = ?,
                           updated_at = NOW()
                       WHERE po_id = ?";
        
        $stmt = $db->prepare($sql_header);
        // Types: i (proj), i (sup), s (phase), s (title), s (status), d (total), i (po_id)
        $stmt->bind_param("iisssdi", $project_id, $supplier_id, $phase, $order_title, $status, $grand_total, $po_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Header Update Error: " . $stmt->error);
        }
        $stmt->close();

        // B. Update Items (Strategy: Delete All Old Items -> Insert New Ones)
        // This handles additions, deletions, and edits simultaneously.
        
        // 1. Delete existing items
        $stmt_del = $db->prepare("DELETE FROM purchase_order_items WHERE po_id = ?");
        $stmt_del->bind_param("i", $po_id);
        if (!$stmt_del->execute()) {
            throw new Exception("Failed to clear old items: " . $stmt_del->error);
        }
        $stmt_del->close();

        // 2. Insert current items
        $sql_item = "INSERT INTO purchase_order_items (po_id, item_name, quantity, unit_cost, total_cost) VALUES (?, ?, ?, ?, ?)";
        $stmt_item = $db->prepare($sql_item);

        foreach ($items as $item) {
            $i_name = trim($item['name']);
            $i_qty = floatval($item['qty']);
            $i_price = floatval($item['price']);
            $i_total = $i_qty * $i_price;

            $stmt_item->bind_param("isddd", $po_id, $i_name, $i_qty, $i_price, $i_total);
            
            if (!$stmt_item->execute()) {
                throw new Exception("Item Insert Error: " . $stmt_item->error);
            }
        }
        $stmt_item->close();

        // --- Commit Transaction ---
        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Purchase Order updated successfully!',
            'po_id' => $po_id
        ]);

    } catch (Exception $e) {
        if (isset($db)) $db->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
}
?>