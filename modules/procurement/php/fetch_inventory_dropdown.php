<?php
// modules/inventory/php/fetch_inventory_dropdown.php
header('Content-Type: application/json');
require_once 'db_connect.php'; // Ensure this file exists in the same folder

// Use correct connection variable
$db = $conn_proc ?? $conn;

// Fetch items with available stock
$sql = "SELECT itemID, item_name as itemName, quantity, unit 
        FROM inventory 
        WHERE quantity > 0 
        ORDER BY item_name ASC";

$result = $db->query($sql);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
?>