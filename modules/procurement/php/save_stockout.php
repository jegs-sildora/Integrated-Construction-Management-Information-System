<?php
// modules/procurement/php/save_stockout.php
header('Content-Type: application/json');

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/Logger.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8mb4");

// Get POST data
$item_id = intval($_POST['stock_itemID'] ?? 0);
$qty = floatval($_POST['stock_quantity'] ?? 0);
$issued_to_input = trim($_POST['stock_issuedTo'] ?? '');
$issued_to_id = intval($_POST['stock_issuedTo_id'] ?? 0);
$project_id = intval($_POST['stock_projectID'] ?? 0);

// Try to resolve employee id from provided input if hidden id wasn't set
if ($issued_to_id <= 0 && $issued_to_input !== '') {
    // Expecting format like "(EMP-2026-010) - Name" or employee code; extract code inside parentheses
    if (preg_match('/\(([^)]+)\)/', $issued_to_input, $m)) {
        $code = $m[1];
    } else {
        $code = $issued_to_input;
    }
    if (!empty($code)) {
        $q = $conn->prepare("SELECT employee_id FROM workforce_employees WHERE employee_code = ? LIMIT 1");
        $q->bind_param('s', $code);
        $q->execute();
        $r = $q->get_result();
        if ($rr = $r->fetch_assoc()) {
            $issued_to_id = intval($rr['employee_id']);
        }
        $q->close();
    }
}

if ($item_id <= 0 || $qty <= 0 || $issued_to_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data. Please select a valid warehouseman.']);
    exit;
}

// Start Transaction
$conn->begin_transaction();

try {
    // 1. Check current stock level (Lock row for safety)
    $checkStmt = $conn->prepare("SELECT quantity FROM procurement_inventory WHERE item_id = ? FOR UPDATE");
    $checkStmt->bind_param("i", $item_id);
    $checkStmt->execute();
    $res = $checkStmt->get_result();
    $item = $res->fetch_assoc();
    $checkStmt->close();

    if (!$item) {
        throw new Exception("Item not found in inventory.");
    }

    if (floatval($item['quantity']) < $qty) {
        throw new Exception("Insufficient stock! Available: " . $item['quantity']);
    }

    // 2. Deduct from Inventory
    $updateStmt = $conn->prepare("UPDATE procurement_inventory SET quantity = quantity - ? WHERE item_id = ?");
    $updateStmt->bind_param("di", $qty, $item_id);
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update inventory.");
    }
    $updateStmt->close();

    // 3. Insert into Stock Out Log
    $insertStmt = $conn->prepare("INSERT INTO procurement_stock_out (item_id, quantity, issued_to_employee_id, date_issued, project_id) VALUES (?, ?, ?, CURDATE(), ?)");
    $project_id_param = $project_id > 0 ? $project_id : null;
    $insertStmt->bind_param("idii", $item_id, $qty, $issued_to_id, $project_id_param);
    if (!$insertStmt->execute()) {
        throw new Exception("Failed to save stock out log.");
    }
    $insertStmt->close();

    $conn->commit();

    // Log the audit trail
    Logger::init($conn);
    Logger::create('Procurement', "Stock Issued: $qty units to Employee #$issued_to_id", $item_id);

    echo json_encode(['status' => 'success', 'message' => 'Stock issued successfully!']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>