<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $person = $_POST['person'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];

    $sql = "UPDATE suppliers SET supplierName=?, contactPerson=?, contactNumber=?, email=?, address=? WHERE supplierID=?";
    $stmt = $conn_proc->prepare($sql);
    $stmt->bind_param("sssssi", $name, $person, $phone, $email, $address, $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    $stmt->close();
    $conn_proc->close();
}
?>