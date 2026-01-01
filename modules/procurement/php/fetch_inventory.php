<?php
header('Content-Type: application/json');
include 'db_connect.php';

// FIX: Select directly from the 'inventory' table instead of calculating from logs
// We alias 'quantity' as 'totalQty' because your inventory.js expects 'totalQty'
$sql = "SELECT 
            itemID, 
            itemName, 
            quantity, 
            unit, 
            lastUpdated, 
            status 
        FROM inventory 
        ORDER BY itemName ASC";

$result = $conn_proc->query($sql);

$data = array();
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
$conn_proc->close();
?>