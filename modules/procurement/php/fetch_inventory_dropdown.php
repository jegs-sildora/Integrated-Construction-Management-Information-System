<?php
// modules/procurement/php/fetch_inventory_dropdown.php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}
$conn->set_charset("utf8mb4");

// Determine project_id from GET or session
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : (isset($_SESSION['current_project_id']) ? intval($_SESSION['current_project_id']) : 0);

// Check if project_id column exists
$hasProjectId = false;
$colCheck = $conn->query("SHOW COLUMNS FROM procurement_inventory LIKE 'project_id'");
if ($colCheck && $colCheck->num_rows > 0) $hasProjectId = true;

// Fetch items with available stock
$sql = "SELECT item_id, item_name, quantity, unit 
        FROM procurement_inventory 
        WHERE quantity > 0";
if ($project_id > 0 && $hasProjectId) {
    $sql .= " AND project_id = " . $project_id;
}
$sql .= " ORDER BY item_name ASC";

$result = $conn->query($sql);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
$conn->close();
?>