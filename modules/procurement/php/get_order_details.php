<?php
// modules/procurement/php/get_order_details.php
header('Content-Type: application/json');

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8mb4");

$po_id = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;

if ($po_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid PO ID']);
    exit;
}

// 1. Fetch PO Header Info
$po_sql = "SELECT po.po_reference, po.project_id, po.phase, po.order_title, po.status, po.total_amount,
                  s.supplier_name, p.project_name
           FROM procurement_purchase_orders po
           LEFT JOIN procurement_suppliers s ON po.supplier_id = s.supplier_id
           LEFT JOIN icmis_projects p ON po.project_id = p.project_id
           WHERE po.po_id = ?";

$stmt = $conn->prepare($po_sql);
$stmt->bind_param("i", $po_id);
$stmt->execute();
$po_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$po_data) {
    echo json_encode(['success' => false, 'message' => 'PO not found']);
    exit;
}

// 2. Fetch PO Items
$items_sql = "SELECT po_item_id, item_name, quantity, unit_cost, total_cost 
              FROM procurement_purchase_order_items 
              WHERE po_id = ?";

$stmt = $conn->prepare($items_sql);
$stmt->bind_param("i", $po_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $row['unit'] = 'pcs'; // Default unit
    $items[] = $row;
}
$stmt->close();

echo json_encode([
    'success' => true, 
    'po' => $po_data, 
    'items' => $items
]);

$conn->close();
?>