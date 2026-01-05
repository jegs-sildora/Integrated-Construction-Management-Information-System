<?php
// modules/procurement/purchase_order/save_order.php

// 1. Start Session & Output Buffering
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

// Setup Headers & Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); 

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
    ob_clean();
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
        $required = ['project_id', 'phase', 'supplier', 'items'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $project_id = intval($data['project_id']);
        $phase = trim($data['phase']);
        $supplier_input = trim($data['supplier']); 
        $order_title = isset($data['title']) ? trim($data['title']) : 'Untitled Order';
        $items = $data['items'];
        
        // Use Logged-in User ID from Session
        $created_by = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null; 

        // --- Resolve Supplier ID ---
        $supplier_id = 0;
        if (is_numeric($supplier_input)) {
            $supplier_id = intval($supplier_input);
        } else {
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

        // --- Generate PO Reference ---
        $year = date('Y');
        $stmt_count = $conn->query("SELECT COUNT(*) as total FROM procurement_purchase_orders WHERE YEAR(order_date) = '$year'");
        $row_count = $stmt_count->fetch_assoc();
        $next_num = $row_count['total'] + 1;
        $po_reference = sprintf("PO-%s-%04d", $year, $next_num);

        // --- Calculate Total ---
        $grand_total = 0;
        foreach ($items as $item) {
            $grand_total += (floatval($item['qty']) * floatval($item['price']));
        }

        // --- Begin Transaction ---
        $conn->begin_transaction();

        // A. Insert Header
        $sql_header = "INSERT INTO procurement_purchase_orders 
                       (po_reference, project_id, supplier_id, phase, order_title, order_date, total_amount, status, created_by_user_id) 
                       VALUES (?, ?, ?, ?, ?, CURDATE(), ?, 'PENDING', ?)";
        
        $stmt = $conn->prepare($sql_header);
        $stmt->bind_param("siissdi", $po_reference, $project_id, $supplier_id, $phase, $order_title, $grand_total, $created_by);
        
        if (!$stmt->execute()) {
            throw new Exception("Header Error: " . $stmt->error);
        }
        $new_po_id = $conn->insert_id;
        $stmt->close();

        // B. Insert Items
        $sql_item = "INSERT INTO procurement_purchase_order_items (po_id, item_name, quantity, unit_cost, total_cost) VALUES (?, ?, ?, ?, ?)";
        $stmt_item = $conn->prepare($sql_item);

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
        $conn->commit();

        ob_clean(); 
        echo json_encode([
            'success' => true,
            'message' => 'Purchase Order created successfully!',
            'po_reference' => $po_reference,
            'po_id' => $new_po_id
        ]);

    } catch (Exception $e) {
        if (isset($conn)) $conn->rollback();
        ob_clean(); 
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
}

$conn->close();
?>