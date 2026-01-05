<?php
// modules/inventory/php/get_approved_pos.php
header('Content-Type: application/json');
require_once '../../../config/database.php';
// Connect to Procurement DB
require_once 'db_connect.php'; 

$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

if ($project_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Project ID']);
    exit;
}

// Fetch Approved POs
// We join with suppliers table to get the name if needed, or if supplier name is stored in PO
$sql = "SELECT po.po_id, po.po_reference, s.supplierName as supplier_name 
        FROM icmis_procurement_inventory_db.purchase_orders po
        LEFT JOIN icmis_procurement_inventory_db.suppliers s ON po.supplier_id = s.supplierID
        WHERE po.project_id = ? AND po.status = 'APPROVED'
        ORDER BY po.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

$pos = [];
while ($row = $result->fetch_assoc()) {
    $pos[] = $row;
}

echo json_encode(['success' => true, 'pos' => $pos]);
?>