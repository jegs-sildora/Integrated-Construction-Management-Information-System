<?php
// modules/inventory/php/get_approved_pos.php
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php'; // Ensure this points to your procurement DB connection

// Use the correct connection variable
$db = $conn_proc ?? $conn;

if (!$db) {
    echo json_encode([]);
    exit;
}

// Fetch only APPROVED orders
$sql = "SELECT po.po_id, po.po_reference, po.order_title, s.supplierName 
        FROM purchase_orders po
        LEFT JOIN suppliers s ON po.supplier_id = s.supplierID
        WHERE po.status = 'APPROVED'
        ORDER BY po.created_at DESC";

$result = $db->query($sql);

$pos = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pos[] = $row;
    }
}

echo json_encode($pos);
?>