<?php
// modules/procurement/purchase_order/delete_order.php

// Return JSON like create/update endpoints so front-end can show toast consistently
// 1. Start session & output buffering
if (session_status() === PHP_SESSION_NONE) session_start();
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/Logger.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8mb4");

$po_id = isset($_POST['po_id']) ? intval($_POST['po_id']) : 0;

if ($po_id <= 0) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID']);
    exit;
}

$conn->begin_transaction();

try {
    // A. Check if the Purchase Order exists
    $check_sql = "SELECT po_reference FROM procurement_purchase_orders WHERE po_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $po_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Purchase Order not found');
    }
    $po = $result->fetch_assoc();
    $check_stmt->close();

    // B. Delete Line Items
    $delete_items_sql = "DELETE FROM procurement_purchase_order_items WHERE po_id = ?";
    $delete_items_stmt = $conn->prepare($delete_items_sql);
    $delete_items_stmt->bind_param("i", $po_id);
    if (!$delete_items_stmt->execute()) {
        throw new Exception('Failed to delete order line items: ' . $delete_items_stmt->error);
    }
    $delete_items_stmt->close();

    // C. Delete Header
    $delete_header_sql = "DELETE FROM procurement_purchase_orders WHERE po_id = ?";
    $delete_header_stmt = $conn->prepare($delete_header_sql);
    $delete_header_stmt->bind_param("i", $po_id);
    if (!$delete_header_stmt->execute()) {
        throw new Exception('Failed to delete purchase order record: ' . $delete_header_stmt->error);
    }
    $delete_header_stmt->close();

    $conn->commit();

    // Log the audit trail
    Logger::init($conn);
    Logger::delete('Procurement', "Purchase Order Deleted: " . $po['po_reference'], $po_id);

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Order deleted successfully',
        'po_reference' => $po['po_reference']
    ]);

} catch (Exception $e) {
    $conn->rollback();
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>