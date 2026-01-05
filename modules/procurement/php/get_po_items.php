<?php
// modules/procurement/php/get_po_items.php
header('Content-Type: application/json');

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}
$conn->set_charset("utf8mb4");

$po_id = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;

if ($po_id <= 0) {
    echo json_encode([]);
    exit;
}

// Fetch items for the specific PO
$stmt = $conn->prepare("SELECT po_item_id, item_name, quantity, unit_cost, total_cost FROM procurement_purchase_order_items WHERE po_id = ?");
$stmt->bind_param("i", $po_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

echo json_encode($items);
$conn->close();
?>