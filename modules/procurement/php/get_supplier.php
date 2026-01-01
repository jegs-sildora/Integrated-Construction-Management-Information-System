<?php
include 'db_connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn_proc->prepare("SELECT * FROM suppliers WHERE supplierID = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(["error" => "Supplier not found"]);
    }
    $stmt->close();
    $conn_proc->close();
}
?>