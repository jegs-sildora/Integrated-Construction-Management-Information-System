<?php
// modules/inventory/php/save_stockout.php
header('Content-Type: application/json');
require_once 'db_connect.php';

$db = $conn_proc ?? $conn;

// Get POST data
$itemID = intval($_POST['stock_itemID'] ?? 0);
$qty = floatval($_POST['stock_quantity'] ?? 0);
$issuedTo = $_POST['stock_issuedTo'] ?? '';
$notes = $_POST['stock_notes'] ?? '';

if ($itemID <= 0 || $qty <= 0 || empty($issuedTo)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data.']);
    exit;
}

// Start Transaction
$db->begin_transaction();

try {
    // 1. Check current stock level (Lock row for safety)
    $checkStmt = $db->prepare("SELECT quantity FROM inventory WHERE itemID = ? FOR UPDATE");
    $checkStmt->bind_param("i", $itemID);
    $checkStmt->execute();
    $res = $checkStmt->get_result();
    $item = $res->fetch_assoc();

    if (!$item) {
        throw new Exception("Item not found in inventory.");
    }

    if ($item['quantity'] < $qty) {
        throw new Exception("Insufficient stock! Available: " . $item['quantity']);
    }

    // 2. Deduct from Inventory
    $updateStmt = $db->prepare("UPDATE inventory SET quantity = quantity - ? WHERE itemID = ?");
    $updateStmt->bind_param("di", $qty, $itemID);
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update inventory.");
    }

    // 3. Generate Reference No (e.g., OUT-20231025-123)
    $refNo = "OUT-" . date('Ymd') . "-" . rand(100, 999);

    // 4. Insert into Stock Out Log
    $insertStmt = $db->prepare("INSERT INTO stock_out (refNo, itemID, quantity, issuedTo, dateIssued, notes) VALUES (?, ?, ?, ?, CURDATE(), ?)");
    $insertStmt->bind_param("siiss", $refNo, $itemID, $qty, $issuedTo, $notes);
    if (!$insertStmt->execute()) {
        throw new Exception("Failed to save log.");
    }

    $db->commit();
    echo json_encode(['status' => 'success', 'message' => 'Stock issued successfully!']);

} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>