<?php
// modules/inventory/php/get_po_details.php
header('Content-Type: application/json');
require_once '../../../config/database.php';
require_once 'db_connect.php';

$po_id = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;

if ($po_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid PO ID']);
    exit;
}

// 1. Fetch PO Header Info
$po_sql = "SELECT po.po_reference, po.project_id, s.supplierName as supplier_name, p.project_name
           FROM icmis_procurement_inventory_db.purchase_orders po
           LEFT JOIN icmis_procurement_inventory_db.suppliers s ON po.supplier_id = s.supplierID
           -- Join Main DB Projects Table (Cross-database join)
           LEFT JOIN icmis.projects p ON po.project_id = p.project_id
           WHERE po.po_id = ?";

$stmt = $conn->prepare($po_sql);
$stmt->bind_param("i", $po_id);
$stmt->execute();
$po_data = $stmt->get_result()->fetch_assoc();

if (!$po_data) {
    echo json_encode(['success' => false, 'message' => 'PO not found']);
    exit;
}

// 2. Fetch PO Items
$items_sql = "SELECT id, item_name, quantity, unit_cost 
              FROM icmis_procurement_inventory_db.purchase_order_items 
              WHERE po_id = ?";

$stmt = $conn->prepare($items_sql);
$stmt->bind_param("i", $po_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    // Optional: You could verify how much has already been received here 
    // by querying inventory_receiving_logs and subtracting.
    // For now, we return the full ordered amount.
    $row['unit'] = 'pcs'; // Default or fetch if column exists
    $items[] = $row;
}

echo json_encode([
    'success' => true, 
    'po' => $po_data, 
    'items' => $items
]);
?>