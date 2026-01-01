<?php
include 'db_connect.php';

// Check if data is being sent via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get the ID from the request
    $orderID = $_POST['orderID'];

    // Prepare Delete Statement
    $stmt = $conn_proc->prepare("DELETE FROM purchaseOrders WHERE orderID = ?");
    $stmt->bind_param("s", $orderID);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }

    $stmt->close();
    $conn_proc->close();
}
?>