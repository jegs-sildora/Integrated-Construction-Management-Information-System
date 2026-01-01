<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $poID = $_POST['poID'];
    $itemName = $_POST['itemName'];
    $quantityReceived = (int)$_POST['quantity']; // Quantity being added
    $unit = $_POST['unit'];
    $receivedBy = $_POST['receivedBy'];
    
    $dateReceived = date("Y-m-d");
    $referenceNo = "SI-" . date("Y") . "-" . rand(1000, 9999);

    // 1. START TRANSACTION
    $conn_proc->begin_transaction();

    try {
        // 2. CHECK IF ITEM EXISTS IN INVENTORY
        // We check by Name because that is how your system links them
        $checkSql = "SELECT itemID, quantity FROM inventory WHERE itemName = ?";
        $stmtCheck = $conn_proc->prepare($checkSql);
        $stmtCheck->bind_param("s", $itemName);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        
        if ($resCheck->num_rows > 0) {
            // A. ITEM EXISTS: UPDATE QUANTITY
            $row = $resCheck->fetch_assoc();
            $updateSql = "UPDATE inventory SET quantity = quantity + ? WHERE itemName = ?";
            $stmtUpdate = $conn_proc->prepare($updateSql);
            $stmtUpdate->bind_param("is", $quantityReceived, $itemName);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        } else {
            // B. ITEM DOES NOT EXIST: INSERT NEW ITEM
            $insertInvSql = "INSERT INTO inventory (itemName, quantity, unit, status) VALUES (?, ?, ?, 'In Stock')";
            $stmtInsertInv = $conn_proc->prepare($insertInvSql);
            $stmtInsertInv->bind_param("sis", $itemName, $quantityReceived, $unit);
            $stmtInsertInv->execute();
            $stmtInsertInv->close();
        }
        $stmtCheck->close();

        // 3. INSERT INTO STOCK_IN LOGS
        $logSql = "INSERT INTO stock_in (referenceNo, poID, itemName, quantityReceived, unit, receivedBy, dateReceived) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtLog = $conn_proc->prepare($logSql);
        $stmtLog->bind_param("sssisss", $referenceNo, $poID, $itemName, $quantityReceived, $unit, $receivedBy, $dateReceived);
        $stmtLog->execute();
        $stmtLog->close();

        // 4. COMMIT
        $conn_proc->commit();
        echo json_encode(["status" => "success", "message" => "Stock received and Inventory updated."]);

    } catch (Exception $e) {
        $conn_proc->rollback();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }

    $conn_proc->close();
}
?>