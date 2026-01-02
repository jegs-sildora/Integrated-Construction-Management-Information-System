<?php
// modules/inventory/php/fetch_inventory.php
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php'; // Adjust path to your connection file

$db = $conn_proc ?? $conn;

if (!$db) {
    echo json_encode([]);
    exit;
}

// Fetch inventory sorted by latest updates
$sql = "SELECT itemID, item_name, category, quantity, unit, unit_cost, last_updated 
        FROM inventory 
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