<?php
header('Content-Type: application/json');
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];

    // Delete query
    $stmt = $conn_proc->prepare("DELETE FROM suppliers WHERE supplierID=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn_proc->error]);
    }

    $stmt->close();
    $conn_proc->close();
}
?>