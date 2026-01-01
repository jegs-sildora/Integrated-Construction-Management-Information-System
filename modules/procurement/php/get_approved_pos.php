<?php
include 'db_connect.php';

// Fetch only APPROVED orders
$sql = "SELECT orderID, itemName, quantity, unit FROM purchaseOrders WHERE status = 'Approved'";
$result = $conn_proc->query($sql);

$data = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
$conn_proc->close();
?>