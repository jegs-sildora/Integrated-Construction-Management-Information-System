<?php
// modules/inventory/php/get_po_items.php
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

$db = $conn_proc ?? $conn;
$po_id = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;

if ($po_id <= 0 || !$db) {
    echo json_encode([]);
    exit;
}

// Fetch items for the specific PO
$stmt = $db->prepare("SELECT id, item_name, quantity, unit_cost FROM purchase_order_items WHERE po_id = ?");
$stmt->bind_param("i", $po_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode($items);
?>