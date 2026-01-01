<?php
// modules/procurement/php/fetch_orders.php

// Disable error display to prevent HTML warnings from breaking JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Include your separate procurement DB connection
require_once 'db_connect.php'; 

// Verify connection variable exists
if (!isset($conn_proc)) {
    echo json_encode(["message" => "Database connection variable not set."]);
    exit;
}

// Select query matching your database schema
// Aliasing columns to match the JS expected format (camelCase)
$sql = "SELECT 
            orderID,
            orderDate,
            itemName,
            itemSubtext,
            quantity,
            unit,
            totalCost,
            supplierName,
            location,
            status
        FROM purchaseorders 
        ORDER BY orderID DESC";

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