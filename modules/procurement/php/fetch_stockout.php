<?php
// modules/procurement/php/fetch_stockout.php
header('Content-Type: application/json');

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}
$conn->set_charset("utf8mb4");

// Join stock_out with inventory to get the Item Name and Unit
$sql = "SELECT 
            so.stock_out_id, 
            pi.item_name, 
            so.quantity, 
            pi.unit, 
            so.issued_to, 
            DATE_FORMAT(so.date_issued, '%b %d, %Y') as date_issued, 
            p.project_name 
        FROM procurement_stock_out so 
        JOIN procurement_inventory pi ON so.item_id = pi.item_id 
        LEFT JOIN icmis_projects p ON so.project_id = p.project_id
        ORDER BY so.date_issued DESC, so.stock_out_id DESC";

$result = $conn->query($sql);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
$conn->close();
?>