<?php
// modules/procurement/php/get_next_id.php
header('Content-Type: application/json');

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(["nextID" => "PO-" . date("Y") . "-001"]);
    exit;
}
$conn->set_charset("utf8mb4");

// Get the latest PO Reference
$sql = "SELECT po_reference FROM procurement_purchase_orders ORDER BY po_id DESC LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastID = $row['po_reference']; 
    // Format is PO-YYYY-XXXX (e.g., PO-2025-0003)
    
    // Split the string to get the number part
    $parts = explode('-', $lastID);
    if (count($parts) >= 3) {
        $year = $parts[1];
        $number = intval($parts[2]);
        
        // Check if year matches current year, reset if new year
        $currentYear = date("Y");
        if ($year != $currentYear) {
            $nextID = "PO-" . $currentYear . "-0001";
        } else {
            $number++;
            $nextID = "PO-" . $year . "-" . str_pad($number, 4, "0", STR_PAD_LEFT);
        }
    } else {
        $nextID = "PO-" . date("Y") . "-0001";
    }
} else {
    // If table is empty, start first record
    $nextID = "PO-" . date("Y") . "-0001";
}

echo json_encode(["nextID" => $nextID]);
$conn->close();
?>