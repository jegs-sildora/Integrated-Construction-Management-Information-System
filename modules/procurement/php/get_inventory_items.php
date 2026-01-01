<?php
header('Content-Type: application/json');
include 'db_connect.php';

$sql = "SELECT T1.itemName, T1.unit, (T1.totalIn - COALESCE(T2.totalOut, 0)) as totalQty, T1.lastUpdated
        FROM (SELECT itemName, unit, SUM(quantityReceived) as totalIn, MAX(dateReceived) as lastUpdated FROM stock_in GROUP BY itemName, unit) as T1
        LEFT JOIN (SELECT itemName, SUM(quantityIssued) as totalOut FROM stock_out GROUP BY itemName) as T2
        ON T1.itemName = T2.itemName";

$result = $conn_proc->query($sql);
$data = array();
if ($result) { while($row = $result->fetch_assoc()) { $data[] = $row; } }
echo json_encode($data);
$conn_proc->close();
?>