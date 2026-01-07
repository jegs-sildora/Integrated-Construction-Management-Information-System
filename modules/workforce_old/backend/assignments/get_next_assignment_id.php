<?php
require '../config.php';
header('Content-Type: application/json');

// Get the last assignment_id
$stmt = $conn->query("SELECT assignment_id FROM assignments ORDER BY assignment_id DESC LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    // Extract numeric part after "ASN"
    $lastId = intval(substr($row['assignment_id'], 3));
    $newId = "ASN" . str_pad($lastId + 1, 3, "0", STR_PAD_LEFT);
} else {
    $newId = "ASN001";
}

echo json_encode([
    "success" => true,
    "assignment_id" => $newId
]);
?>
