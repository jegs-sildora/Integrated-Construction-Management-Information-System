<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture inputs using correct HTML names
    $id = $_POST['input_orderID'];
    $date = $_POST['input_date'];
    $item = $_POST['input_itemName'];
    $sub = $_POST['input_itemSubtext'];
    $qty = $_POST['input_quantity'];
    $unit = $_POST['input_unit'];
    $cost = $_POST['input_cost'];
    $supp = $_POST['input_supplier'];
    $loc = $_POST['input_location'];
    $stat = $_POST['input_status'];

    $stmt = $conn_proc->prepare("INSERT INTO purchaseOrders (orderID, orderDate, itemName, itemSubtext, quantity, unit, totalCost, supplierName, location, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssisdsss", $id, $date, $item, $sub, $qty, $unit, $cost, $supp, $loc, $stat);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    $stmt->close();
    $conn_proc->close();
}
?>