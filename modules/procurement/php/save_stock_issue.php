<?php
// modules/procurement/php/save_stock_issue.php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}
$conn->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_id = intval($_POST['stock_itemID'] ?? 0);
    $qtyToIssue = intval($_POST['stock_quantity'] ?? 0);
    $issuedTo = trim($_POST['stock_issuedTo'] ?? '');
    $project_id = intval($_POST['stock_projectID'] ?? 0);

    if ($item_id <= 0 || $qtyToIssue <= 0 || empty($issuedTo)) {
        echo json_encode(["status" => "error", "message" => "Invalid input data"]);
        exit;
    }

    $conn->begin_transaction();

    try {
        // Check current stock
        $checkSql = "SELECT quantity FROM procurement_inventory WHERE item_id = ? FOR UPDATE";
        $stmtCheck = $conn->prepare($checkSql);
        $stmtCheck->bind_param("i", $item_id);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        $row = $resCheck->fetch_assoc();
        $currentStock = intval($row['quantity'] ?? 0);
        $stmtCheck->close();

        if ($currentStock < $qtyToIssue) {
            throw new Exception("Insufficient stock! Available: $currentStock, Requested: $qtyToIssue");
        }

        // Deduct from inventory
        $updateSql = "UPDATE procurement_inventory SET quantity = quantity - ? WHERE item_id = ?";
        $stmtUpdate = $conn->prepare($updateSql);
        $stmtUpdate->bind_param("ii", $qtyToIssue, $item_id);
        if (!$stmtUpdate->execute()) {
            throw new Exception("Failed to update inventory.");
        }
        $stmtUpdate->close();

        // Insert into stock_out
        $insertSql = "INSERT INTO procurement_stock_out (item_id, quantity, issued_to, date_issued, project_id) VALUES (?, ?, ?, CURDATE(), ?)";
        $stmtInsert = $conn->prepare($insertSql);
        $project_param = $project_id > 0 ? $project_id : null;
        $stmtInsert->bind_param("iisi", $item_id, $qtyToIssue, $issuedTo, $project_param);
        if (!$stmtInsert->execute()) {
            throw new Exception("Failed to record transaction.");
        }
        $stmtInsert->close();

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Stock issued successfully!"]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    
    $conn->close();
}
?>