<?php
// modules/inventory/php/fetch_stockout.php
header('Content-Type: application/json');
require_once 'db_connect.php';

$db = $conn_proc ?? $conn;

// Join stock_out with inventory to get the Item Name and Unit
$sql = "SELECT 
            so.refNo, 
            i.item_name as itemName, 
            so.quantity, 
            i.unit, 
            so.issuedTo, 
            DATE_FORMAT(so.dateIssued, '%b %d, %Y') as dateIssued, 
            so.notes 
        FROM stock_out so 
        JOIN inventory i ON so.itemID = i.itemID 
        ORDER BY so.dateIssued DESC, so.id DESC";

$result = $db->query($sql);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
?>