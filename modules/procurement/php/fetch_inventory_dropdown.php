<?php
header('Content-Type: application/json');
include 'db_connect.php';

// Select only items that actually have stock
$sql = "SELECT itemID, itemName, quantity, unit FROM inventory WHERE quantity > 0";
$result = $conn_proc->query($sql);

$data = array();
while($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
$conn_proc->close();
?>