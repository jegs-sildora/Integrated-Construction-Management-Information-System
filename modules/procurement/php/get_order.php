<?php
header('Content-Type: application/json');
include 'db_connect.php';

if (isset($_GET['orderID'])) {
    $id = $_GET['orderID'];
    $sql = "SELECT * FROM purchaseOrders WHERE orderID = ?";
    $stmt = $conn_proc->prepare($sql);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(["error" => "Order not found"]);
    }
    $stmt->close();
}
$conn_proc->close();
?>