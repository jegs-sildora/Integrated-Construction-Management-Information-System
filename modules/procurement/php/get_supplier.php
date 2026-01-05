<?php
// modules/procurement/php/get_supplier.php
header('Content-Type: application/json');

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}
$conn->set_charset("utf8mb4");

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT supplier_id, supplier_name, contact_person, contact_number, email, address, status FROM procurement_suppliers WHERE supplier_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        echo json_encode($data ? $data : ["error" => "Supplier not found"]);
    } else {
        echo json_encode(["error" => "Query failed"]);
    }
    $stmt->close();
}

$conn->close();
?>