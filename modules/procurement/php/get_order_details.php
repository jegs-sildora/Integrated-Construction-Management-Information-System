<?php
// modules/procurement/php/get_order_details.php
header('Content-Type: application/json');

// 1. Connect to Procurement DB
require_once __DIR__ . '/db_connect.php'; 
$proc_conn = $conn_proc ?? $conn;

// 2. Connect to Main DB (for Project & User info)
// Adjust the path to config/database.php as needed
require_once __DIR__ . '/../../../config/database.php';
$main_conn = $conn;

$po_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($po_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Order ID']);
    exit;
}

// 3. Fetch PO Header + Supplier
$sql = "SELECT po.*, s.supplierName 
        FROM purchase_orders po 
        LEFT JOIN suppliers s ON po.supplier_id = s.supplierID 
        WHERE po.po_id = ?";
$stmt = $proc_conn->prepare($sql);
$stmt->bind_param("i", $po_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

// 4. Fetch Project Name (From Main DB)
$proj_sql = "SELECT name FROM projects WHERE project_id = ?";
$stmt_proj = $main_conn->prepare($proj_sql);
$stmt_proj->bind_param("i", $order['project_id']);
$stmt_proj->execute();
$proj_res = $stmt_proj->get_result()->fetch_assoc();
$order['project_name'] = $proj_res ? $proj_res['name'] : 'Unknown Project';

// 5. Fetch Creator Name (From Main DB)
$user_sql = "SELECT full_name FROM users WHERE user_id = ?";
$stmt_user = $main_conn->prepare($user_sql);
$stmt_user->bind_param("i", $order['created_by']);
$stmt_user->execute();
$user_res = $stmt_user->get_result()->fetch_assoc();
$order['created_by_name'] = $user_res ? $user_res['full_name'] : 'Unknown User';

// 6. Fetch Order Items
$item_sql = "SELECT * FROM purchase_order_items WHERE po_id = ?";
$stmt_items = $proc_conn->prepare($item_sql);
$stmt_items->bind_param("i", $po_id);
$stmt_items->execute();
$items_res = $stmt_items->get_result();
$items = [];
while ($row = $items_res->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode([
    'success' => true, 
    'order' => $order, 
    'items' => $items
]);
?>