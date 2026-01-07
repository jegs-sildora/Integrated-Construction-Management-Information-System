<?php
// modules/procurement/php/fetch_orders.php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(["message" => "Database connection failed."]);
    exit;
}
$conn->set_charset("utf8mb4");

// Get project_id from request if provided
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

// Build query with correct table and column names
$sql = "SELECT 
            po.po_id,
            po.po_reference,
            po.project_id,
            po.supplier_id,
            po.phase_id,
            po.order_title,
            DATE_FORMAT(po.order_date, '%Y-%m-%d') as order_date,
            po.total_amount,
            po.status,
            s.supplier_name,
            p.project_name
        FROM procurement_purchase_orders po
        LEFT JOIN procurement_suppliers s ON po.supplier_id = s.supplier_id
        LEFT JOIN icmis_projects p ON po.project_id = p.project_id";

if ($project_id > 0) {
    $sql .= " WHERE po.project_id = $project_id";
}

$sql .= " ORDER BY po.order_date DESC, po.po_id DESC";

$result = $conn->query($sql);

$data = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
$conn->close();
?>