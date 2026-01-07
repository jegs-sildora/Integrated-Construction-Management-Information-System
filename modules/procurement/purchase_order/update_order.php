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

// 2. Use centralized config
require_once __DIR__ . '/../../../config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8mb4");

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

        // Resolve phase_id: accept numeric `phase_id`, numeric `phase`, or lookup by `phase` name
        $phase_id = 0;
        if (isset($data['phase_id']) && is_numeric($data['phase_id'])) {
            $phase_id = intval($data['phase_id']);
        } elseif (isset($data['phase']) && is_numeric($data['phase'])) {
            $phase_id = intval($data['phase']);
        } elseif (isset($data['phase']) && !empty($data['phase'])) {
            $phase_name = trim($data['phase']);
            $stmt_phase = $conn->prepare("SELECT phase_id FROM icmis_project_phases WHERE phase_name = ? AND project_id = ? LIMIT 1");
            if ($stmt_phase) {
                $stmt_phase->bind_param("si", $phase_name, $project_id);
                $stmt_phase->execute();
                $res_phase = $stmt_phase->get_result();
                if ($row_phase = $res_phase->fetch_assoc()) {
                    $phase_id = intval($row_phase['phase_id']);
                }
                $stmt_phase->close();
            }
        }
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
            $stmt_sup = $conn->prepare("SELECT supplier_id FROM procurement_suppliers WHERE supplier_name = ? LIMIT 1");
            $stmt_sup->bind_param("s", $supplier_input);
            $stmt_sup->execute();
            $res_sup = $stmt_sup->get_result();
            if ($row_sup = $res_sup->fetch_assoc()) {
                $supplier_id = $row_sup['supplier_id'];
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
        $conn->begin_transaction();

        // A. Update Header Table
        $sql_header = "UPDATE procurement_purchase_orders 
                       SET project_id = ?, 
                           supplier_id = ?, 
                           phase_id = ?, 
                           order_title = ?, 
                           status = ?, 
                           total_amount = ?
                       WHERE po_id = ?";
        
        $stmt = $conn->prepare($sql_header);
        // Types: i (proj), i (sup), i (phase_id), s (title), s (status), d (total), i (po_id)
        $stmt->bind_param("iiissdi", $project_id, $supplier_id, $phase_id, $order_title, $status, $grand_total, $po_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Header Update Error: " . $stmt->error);
        }
        $stmt->close();

        // B. Update Items (Strategy: Delete All Old Items -> Insert New Ones)
        // This handles additions, deletions, and edits simultaneously.
        
        // 1. Delete existing items
        $stmt_del = $conn->prepare("DELETE FROM procurement_purchase_order_items WHERE po_id = ?");
        $stmt_del->bind_param("i", $po_id);
        if (!$stmt_del->execute()) {
            throw new Exception("Failed to clear old items: " . $stmt_del->error);
        }
        $stmt_del->close();

        // 2. Insert current items
        $sql_item = "INSERT INTO procurement_purchase_order_items (po_id, item_name, quantity, unit_cost, total_cost) VALUES (?, ?, ?, ?, ?)";
        $stmt_item = $conn->prepare($sql_item);

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
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Purchase Order updated successfully!',
            'po_id' => $po_id
        ]);

    } catch (Exception $e) {
        if (isset($conn)) $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
}

$conn->close();
?>