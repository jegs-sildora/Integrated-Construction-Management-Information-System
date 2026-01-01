<?php
header('Content-Type: application/json');
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $person = $_POST['person'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $status = $_POST['status']; // Capture status

    if (!empty($id)) {
        // UPDATE EXISTING
        $stmt = $conn_proc->prepare("UPDATE suppliers SET supplierName=?, contactPerson=?, contactNumber=?, email=?, address=?, status=? WHERE supplierID=?");
        $stmt->bind_param("ssssssi", $name, $person, $phone, $email, $address, $status, $id);
    } else {
        // INSERT NEW
        $stmt = $conn_proc->prepare("INSERT INTO suppliers (supplierName, contactPerson, contactNumber, email, address, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $person, $phone, $email, $address, $status);
    }

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    $stmt->close();
    $conn_proc->close();
}
?>