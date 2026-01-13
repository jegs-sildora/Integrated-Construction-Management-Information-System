<?php
// modules/procurement/php/get_approved_pos.php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8mb4");

// Get project_id from GET or session
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : (isset($_SESSION['current_project_id']) ? intval($_SESSION['current_project_id']) : 0);

if ($project_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Project ID']);
    exit;
}

// Fetch Approved POs
$sql = "SELECT po.po_id, po.po_reference, s.supplier_name 
        FROM procurement_purchase_orders po
        LEFT JOIN procurement_suppliers s ON po.supplier_id = s.supplier_id
        WHERE po.project_id = ? AND po.status = 'APPROVED'
        ORDER BY po.order_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

$pos = [];
while ($row = $result->fetch_assoc()) {
    $pos[] = $row;
}
$stmt->close();

echo json_encode(['success' => true, 'pos' => $pos]);
$conn->close();
?>