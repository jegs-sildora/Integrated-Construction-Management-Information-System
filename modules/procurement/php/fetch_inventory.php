<?php
// modules/procurement/php/fetch_inventory.php
header('Content-Type: application/json');

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}
$conn->set_charset("utf8mb4");

// Optional filters
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$phase_id = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;

// Build query - filter by project_id if provided (inventory table now stores project_id)
$sql = "SELECT item_id, item_name, category, unit, unit_cost, quantity, project_id, phase_id, last_updated
        FROM procurement_inventory";

$params = [];
$types = '';
$conditions = [];

if ($project_id > 0) {
    $conditions[] = "project_id = ?";
    $types .= 'i';
    $params[] = $project_id;
}
if ($phase_id > 0) {
    $conditions[] = "phase_id = ?";
    $types .= 'i';
    $params[] = $phase_id;
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}
$sql .= " ORDER BY item_name ASC";

$data = [];

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Ensure numeric types and friendly last_updated format
        $row['quantity'] = floatval($row['quantity'] ?? 0);
        $row['unit_cost'] = floatval($row['unit_cost'] ?? 0);
        $row['last_updated'] = $row['last_updated'] ? date('M d, Y', strtotime($row['last_updated'])) : '-';
        
        // Only include items with positive quantity
        if ($row['quantity'] > 0) {
            $data[] = $row;
        }
    }
}

echo json_encode($data);
$conn->close();
?>