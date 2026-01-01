<?php
include 'db_connect.php';
$sql = "SELECT * FROM suppliers ORDER BY supplierID DESC";
$result = $conn_proc->query($sql);
$data = array();
while($row = $result->fetch_assoc()) { $data[] = $row; }
echo json_encode($data);
?>