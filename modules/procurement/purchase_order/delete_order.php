<?php
// modules/procurement/php/delete_order.php

// 1. Output Buffering (Crucial: prevents PHP warnings/HTML from breaking JSON)
ob_start();

// Disable display of errors in output (logs them instead)
error_reporting(E_ALL);
ini_set('display_errors', 0); 

header('Content-Type: application/json');

// 2. Include Database Connection
require_once __DIR__ . '/db_connect.php'; 

// Ensure we use the active connection variable
$db = $conn_proc ?? $conn;

if (!isset($db) || $db->connect_error) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// 3. Get Input
// FIX: Using 'po_id' to match your database schema and JS
$po_id = isset($_POST['po_id']) ? intval($_POST['po_id']) : 0;

if ($po_id <= 0) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Invalid Purchase Order ID'
    ]);
    exit;
}

// 4. Start Transaction
$db->begin_transaction();

try {
    // A. Check if the Purchase Order exists 
    // FIX: Table name is 'purchase_orders' (with underscore)
    $check_sql = "SELECT po_reference, status FROM purchase_orders WHERE po_id = ?";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->bind_param("i", $po_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Purchase Order not found');
    }
    
    $po = $result->fetch_assoc();
    $check_stmt->close();
    
    // B. Delete Line Items first
    // FIX: Table name is 'purchase_order_items'
    $delete_items_sql = "DELETE FROM purchase_order_items WHERE po_id = ?";
    $delete_items_stmt = $db->prepare($delete_items_sql);
    $delete_items_stmt->bind_param("i", $po_id);
    
    if (!$delete_items_stmt->execute()) {
        throw new Exception('Failed to delete order line items: ' . $delete_items_stmt->error);
    }
    $delete_items_stmt->close();
    
    // C. Delete the Purchase Order Header
    // FIX: Table name is 'purchase_orders'
    $delete_header_sql = "DELETE FROM purchase_orders WHERE po_id = ?";
    $delete_header_stmt = $db->prepare($delete_header_sql);
    $delete_header_stmt->bind_param("i", $po_id);
    
    if (!$delete_header_stmt->execute()) {
        throw new Exception('Failed to delete purchase order record: ' . $delete_header_stmt->error);
    }
    $delete_header_stmt->close();
    
    // D. Commit transaction
    $db->commit();
    
    ob_clean(); // Clear any invisible buffer text before JSON output
    echo json_encode([
        'success' => true,
        'message' => 'Order ' . $po['po_reference'] . ' deleted successfully'
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    ob_clean(); // Clear buffer on error too
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$db->close();
?>