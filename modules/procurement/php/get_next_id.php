<?php
include 'db_connect.php';

// Get the latest Order ID
$sql = "SELECT orderID FROM purchaseOrders ORDER BY orderID DESC LIMIT 1";
$result = $conn_proc->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastID = $row['orderID']; 
    // Format is PO-YYYY-XXX (e.g., PO-2025-003)
    
    // Split the string to get the number part
    $parts = explode('-', $lastID);
    $year = $parts[1];
    $number = intval($parts[2]); // Turns "003" into number 3
    
    // Check if year matches current year, reset if new year
    $currentYear = date("Y");
    if ($year != $currentYear) {
        $nextID = "PO-" . $currentYear . "-001";
    } else {
        $number++; // Increment the number
        // Pad with zeros (e.g., 4 becomes "004")
        $nextID = "PO-" . $year . "-" . str_pad($number, 3, "0", STR_PAD_LEFT);
    }
} else {
    // If table is empty, start first record
    $nextID = "PO-" . date("Y") . "-001";
}

echo json_encode(["nextID" => $nextID]);
$conn_proc->close();
?>