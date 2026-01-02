<?php
// modules/procurement/purchase_order/save_order.php

// 1. Setup Headers & Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Turn off display to ensure JSON is valid

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Connect to Procurement Database
// Adjust this path to match your procurement DB connection file
// Based on your create_order.php, it seems to be:
include __DIR__ . '/../php/db_connect.php'; 

if (!isset($conn_proc) || $conn_proc->connect_error) {
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
        $required = ['project_id', 'phase', 'supplier', 'items'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $project_id = intval($data['project_id']);
        $phase = trim($data['phase']);
        $supplier_input = trim($data['supplier']); // This might be Name or ID
        $order_title = isset($data['title']) ? trim($data['title']) : 'Untitled Order';
        $items = $data['items'];
        $created_by = 1; // Default User ID (Replace with session ID in production)

        // --- Resolve Supplier ID ---
        // Since the datalist sends the Name, we need to look up the ID
        $supplier_id = 0;
        
        // Check if input is numeric (ID) or string (Name)
        if (is_numeric($supplier_input)) {
            $supplier_id = intval($supplier_input);
        } else {
            // Look up ID by Name
            $stmt_sup = $conn_proc->prepare("SELECT supplierID FROM suppliers WHERE supplierName = ? LIMIT 1");
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

        // --- Generate PO Reference (PO-YYYY-XXXX) ---
        $year = date('Y');
        $stmt_count = $conn_proc->query("SELECT COUNT(*) as total FROM purchase_orders WHERE YEAR(created_at) = '$year'");
        $row_count = $stmt_count->fetch_assoc();
        $next_num = $row_count['total'] + 1;
        $po_reference = sprintf("PO-%s-%04d", $year, $next_num);

        // --- Calculate Total Amount ---
        $grand_total = 0;
        foreach ($items as $item) {
            $grand_total += (floatval($item['qty']) * floatval($item['price']));
        }

        // --- Begin Transaction ---
        $conn_proc->begin_transaction();

        // A. Insert Header
        $sql_header = "INSERT INTO purchase_orders 
                       (po_reference, project_id, supplier_id, phase, order_title, total_amount, status, created_by, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, 'PENDING', ?, NOW())";
        
        $stmt = $conn_proc->prepare($sql_header);
        $stmt->bind_param("siissdi", $po_reference, $project_id, $supplier_id, $phase, $order_title, $grand_total, $created_by);
        
        if (!$stmt->execute()) {
            throw new Exception("Header Error: " . $stmt->error);
        }
        $new_po_id = $conn_proc->insert_id;
        $stmt->close();

        // B. Insert Items
        $sql_item = "INSERT INTO purchase_order_items (po_id, item_name, quantity, unit_cost, total_cost) VALUES (?, ?, ?, ?, ?)";
        $stmt_item = $conn_proc->prepare($sql_item);

        foreach ($items as $item) {
            $i_name = trim($item['name']);
            $i_qty = floatval($item['qty']);
            $i_price = floatval($item['price']);
            $i_total = $i_qty * $i_price;

            $stmt_item->bind_param("isddd", $new_po_id, $i_name, $i_qty, $i_price, $i_total);
            
            if (!$stmt_item->execute()) {
                throw new Exception("Item Error: " . $stmt_item->error);
            }
        }
        $stmt_item->close();

        // --- Commit ---
        $conn_proc->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Purchase Order created successfully!',
            'po_reference' => $po_reference,
            'po_id' => $new_po_id
        ]);

    } catch (Exception $e) {
        if (isset($conn_proc)) $conn_proc->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
}
?>