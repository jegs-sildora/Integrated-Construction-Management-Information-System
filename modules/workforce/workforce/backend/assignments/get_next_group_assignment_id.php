<?php
require '../config.php';
header('Content-Type: application/json');

// Get the last group_assignment_id
$stmt = $conn->query("SELECT group_assignment_id FROM group_assignments ORDER BY group_assignment_id DESC LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    // Extract numeric part after "GASN"
    $lastId = intval(substr($row['group_assignment_id'], 4));
    $newId = "GASN" . str_pad($lastId + 1, 3, "0", STR_PAD_LEFT);
} else {
    $newId = "GASN001";
}

echo json_encode([
    "success" => true,
    "group_assignment_id" => $newId
]);
