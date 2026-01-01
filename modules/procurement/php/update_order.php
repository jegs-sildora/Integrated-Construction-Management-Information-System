<?php
// 1. SILENCE ERRORS (Prevent HTML leakage breaking JSON)
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
include 'db_connect.php';

// 2. CHECK CONNECTION
if ($conn_proc->connect_error) {
    echo json_encode(["status" => "error", "message" => "DB Connection failed"]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 3. GET DATA (Using the 'input_' names from your HTML)
    $orderID = $_POST['input_orderID'];
    $date = $_POST['input_date'];
    $itemName = $_POST['input_itemName'];
    $subtext = $_POST['input_itemSubtext'];
    $qty = $_POST['input_quantity'];
    $unit = $_POST['input_unit'];
    $cost = $_POST['input_cost'];
    $supplier = $_POST['input_supplier'];
    $location = $_POST['input_location'];
    $status = $_POST['input_status'];

    // 4. SQL UPDATE COMMAND
    $sql = "UPDATE purchaseOrders SET 
            orderDate = ?, 
            itemName = ?, 
            itemSubtext = ?, 
            quantity = ?, 
            unit = ?, 
            totalCost = ?, 
            supplierName = ?, 
            location = ?, 
            status = ? 
            WHERE orderID = ?";

    $stmt = $conn_proc->prepare($sql);
    
    // Bind parameters (s=string, i=int, d=decimal)
    $stmt->bind_param("sssisdssss", $date, $itemName, $subtext, $qty, $unit, $cost, $supplier, $location, $status, $orderID);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Update failed: " . $stmt->error]);
    }

    $stmt->close();
    $conn_proc->close();
}
?>