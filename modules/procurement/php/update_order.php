<?php
// modules/procurement/php/update_order.php
// Note: This file is for legacy compatibility. Main update_order is in purchase_order folder.
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}
$conn->set_charset("utf8mb4");

echo json_encode(["status" => "error", "message" => "Please use the purchase_order/update_order.php endpoint"]);
$conn->close();
?>