<?php
// modules/procurement/php/update_supplier.php
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

    if ($id <= 0 || empty($name)) {
        echo json_encode(["status" => "error", "message" => "Invalid input"]);
        exit;
    }

    $sql = "UPDATE procurement_suppliers SET supplier_name=?, contact_person=?, contact_number=?, email=?, address=? WHERE supplier_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $name, $person, $phone, $email, $address, $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    $stmt->close();
}

$conn->close();
?>