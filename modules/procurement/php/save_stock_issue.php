<?php
// Prevent HTML errors breaking JSON
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $itemID = $_POST['stock_itemID']; // Make sure this matches JS FormData
    $qtyToIssue = (int)$_POST['stock_quantity'];
    $issuedTo = $_POST['stock_issuedTo'];
    $notes = $_POST['stock_notes'];
    $date = date('Y-m-d');
    
    // Generate a Reference Number (e.g., OUT-2025-001)
    // For simplicity here, we use a random string or you can implement a counter
    $refNo = "OUT-" . date("Y") . "-" . rand(1000, 9999); 

    // 1. START TRANSACTION (Crucial for inventory accuracy)
    $conn_proc->begin_transaction();

    try {
        // 2. CHECK CURRENT STOCK
        $checkSql = "SELECT quantity FROM inventory WHERE itemID = ? FOR UPDATE";
        $stmtCheck = $conn_proc->prepare($checkSql);
        $stmtCheck->bind_param("s", $itemID);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        $row = $resCheck->fetch_assoc();
        $currentStock = (int)$row['quantity'];
        $stmtCheck->close();

        // 3. VALIDATION: Is there enough stock?
        if ($currentStock < $qtyToIssue) {
            throw new Exception("Insufficient stock! Available: $currentStock, Requested: $qtyToIssue");
        }

        // 4. DEDUCT FROM INVENTORY
        $updateSql = "UPDATE inventory SET quantity = quantity - ? WHERE itemID = ?";
        $stmtUpdate = $conn_proc->prepare($updateSql);
        $stmtUpdate->bind_param("is", $qtyToIssue, $itemID);
        if (!$stmtUpdate->execute()) {
            throw new Exception("Failed to update inventory.");
        }
        $stmtUpdate->close();

        // 5. INSERT INTO STOCK_OUT LOGS
        // Assuming table name is 'stock_out' or similar
        $insertSql = "INSERT INTO stock_out (refNo, itemID, quantity, issuedTo, dateIssued, notes) VALUES (?, ?, ?, ?, ?, ?)";
        $stmtInsert = $conn_proc->prepare($insertSql);
        $stmtInsert->bind_param("ssisss", $refNo, $itemID, $qtyToIssue, $issuedTo, $date, $notes);
        if (!$stmtInsert->execute()) {
            throw new Exception("Failed to record transaction.");
        }
        $stmtInsert->close();

        // 6. COMMIT TRANSACTION
        $conn_proc->commit();
        echo json_encode(["status" => "success", "message" => "Stock issued successfully! Ref: $refNo"]);

    } catch (Exception $e) {
        // ROLLBACK IF ANY ERROR
        $conn_proc->rollback();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    
    $conn_proc->close();
}
?>