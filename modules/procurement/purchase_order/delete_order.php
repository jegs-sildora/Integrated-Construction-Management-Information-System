<?php
// modules/procurement/php/delete_order.php

header('Content-Type: application/json');

// 1. Include Database Connection
// Adjust path to point to your procurement db connection
require_once __DIR__ . '/db_connect.php'; 

// Ensure we use the active connection variable (handling potential naming differences)
$db = $conn_proc ?? $conn;

if (!isset($db) || $db->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// 2. Get Input
// Your JS sends FormData, which populates $_POST, not file_get_contents('php://input')
$po_id = isset($_POST['po_id']) ? intval($_POST['po_id']) : 0;

// Validate input
if ($po_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid Purchase Order ID'
    ]);
    exit;
}

// 3. Start Transaction
$db->begin_transaction();

try {
    // A. Check if the Purchase Order exists
    $check_sql = "SELECT po_id, po_reference, status FROM purchase_orders WHERE po_id = ?";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->bind_param("i", $po_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Purchase Order not found');
    }
    
    $po = $result->fetch_assoc();
    $check_stmt->close();
    
    // Optional: Restrict deletion if status is already APPROVED or COMPLETED
    // if ($po['status'] === 'APPROVED' || $po['status'] === 'COMPLETED') {
    //     throw new Exception('Cannot delete an active or completed order.');
    // }
    
    // B. Delete Line Items first (Manual cleanup to ensure integrity)
    $delete_items_sql = "DELETE FROM purchase_order_items WHERE po_id = ?";
    $delete_items_stmt = $db->prepare($delete_items_sql);
    $delete_items_stmt->bind_param("i", $po_id);
    
    if (!$delete_items_stmt->execute()) {
        throw new Exception('Failed to delete order line items');
    }
    $delete_items_stmt->close();
    
    // C. Delete the Purchase Order Header
    $delete_header_sql = "DELETE FROM purchase_orders WHERE po_id = ?";
    $delete_header_stmt = $db->prepare($delete_header_sql);
    $delete_header_stmt->bind_param("i", $po_id);
    
    if (!$delete_header_stmt->execute()) {
        throw new Exception('Failed to delete purchase order record');
    }
    $delete_header_stmt->close();
    
    // D. Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order ' . $po['po_reference'] . ' deleted successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$db->close();
?>