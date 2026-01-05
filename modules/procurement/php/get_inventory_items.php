<?php
// modules/procurement/php/get_inventory_items.php
header('Content-Type: application/json');

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}
$conn->set_charset("utf8mb4");

// Query to calculate available stock from stock_in minus stock_out
$sql = "SELECT 
            pi.item_id,
            pi.item_name,
            pi.unit,
            pi.quantity as total_qty,
            pi.unit_cost,
            pi.last_updated
        FROM procurement_inventory pi
        WHERE pi.quantity > 0
        ORDER BY pi.item_name ASC";

$result = $conn->query($sql);
$data = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
$conn->close();
?>