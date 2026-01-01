<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // These IDs must match the 'name' attributes in your HTML form
    $itemID = $_POST['stock_itemID']; 
    $qtyToIssue = (int)$_POST['stock_quantity'];
    $issuedTo = $_POST['stock_issuedTo'];
    $notes = $_POST['stock_notes'];
    $date = date('Y-m-d');
    
    // Create a random Reference Number
    $refNo = "OUT-" . date("Y") . "-" . rand(1000, 9999); 

    $conn_proc->begin_transaction();

    try {
        // 1. Check Stock
        $stmtCheck = $conn_proc->prepare("SELECT quantity FROM inventory WHERE itemID = ? FOR UPDATE");
        $stmtCheck->bind_param("s", $itemID);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        $row = $resCheck->fetch_assoc();
        
        if (!$row) { throw new Exception("Item not found."); }
        
        $currentStock = (int)$row['quantity'];
        $stmtCheck->close();

        if ($currentStock < $qtyToIssue) {
            throw new Exception("Insufficient stock! Available: $currentStock");
        }

        // 2. Deduct Stock
        $stmtUpdate = $conn_proc->prepare("UPDATE inventory SET quantity = quantity - ? WHERE itemID = ?");
        $stmtUpdate->bind_param("is", $qtyToIssue, $itemID);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        // 3. Record Log
        $stmtInsert = $conn_proc->prepare("INSERT INTO stock_out (refNo, itemID, quantity, issuedTo, dateIssued, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtInsert->bind_param("ssisss", $refNo, $itemID, $qtyToIssue, $issuedTo, $date, $notes);
        $stmtInsert->execute();
        $stmtInsert->close();

        $conn_proc->commit();
        echo json_encode(["status" => "success", "message" => "Stock issued successfully!"]);

    } catch (Exception $e) {
        $conn_proc->rollback();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    
    $conn_proc->close();
}
?>