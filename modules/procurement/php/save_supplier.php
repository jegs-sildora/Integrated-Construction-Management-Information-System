<?php
// modules/procurement/php/save_supplier.php
header('Content-Type: application/json');

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}
$conn->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = trim($_POST['name'] ?? '');
    $person = trim($_POST['person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');

    if (empty($name)) {
        echo json_encode(["status" => "error", "message" => "Supplier name is required"]);
        exit;
    }

    if ($id > 0) {
        // UPDATE EXISTING
        $stmt = $conn->prepare("UPDATE procurement_suppliers SET supplier_name=?, contact_person=?, contact_number=?, email=?, address=?, status=? WHERE supplier_id=?");
        $stmt->bind_param("ssssssi", $name, $person, $phone, $email, $address, $status, $id);
    } else {
        // INSERT NEW
        $stmt = $conn->prepare("INSERT INTO procurement_suppliers (supplier_name, contact_person, contact_number, email, address, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $person, $phone, $email, $address, $status);
    }

    if ($stmt->execute()) {
        $new_id = $id > 0 ? $id : $stmt->insert_id;
        echo json_encode(["status" => "success", "supplier_id" => $new_id]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    $stmt->close();
}

$conn->close();
?>