<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
include 'db_connect.php';

if ($conn_proc->connect_error) {
    echo json_encode([]);
    exit();
}

// Ensure the table names here match your database exactly
$sql = "SELECT 
            s.id,
            s.refNo, 
            COALESCE(i.itemName, 'Unknown Item') as itemName, 
            s.quantity, 
            COALESCE(i.unit, '-') as unit, 
            s.issuedTo, 
            s.dateIssued, 
            s.notes 
        FROM stock_out s
        LEFT JOIN inventory i ON s.itemID = i.itemID
        ORDER BY s.dateIssued DESC, s.id DESC";

$result = $conn_proc->query($sql);

$data = array();
if ($result) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
$conn_proc->close();
?>