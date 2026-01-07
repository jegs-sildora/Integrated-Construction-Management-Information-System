<?php
// modules/procurement/php/update_stockin.php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset('utf8mb4');

function sendJson($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(false, 'Invalid request method');
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) sendJson(false, 'Invalid JSON');

$stock_id = intval($data['stock_in_id'] ?? 0);
$new_qty = is_numeric($data['quantity_received']) ? intval($data['quantity_received']) : null;
$new_date = trim($data['date_received'] ?? '');

if ($stock_id <= 0 || $new_qty === null) sendJson(false, 'Missing parameters');

$conn->begin_transaction();
try {
    // Fetch existing stock_in row (use item_id reference)
    $stmt = $conn->prepare("SELECT stock_in_id, po_id, item_id, quantity_received FROM procurement_stock_in WHERE stock_in_id = ? FOR UPDATE");
    $stmt->bind_param('i', $stock_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) throw new Exception('Stock record not found');
    $row = $res->fetch_assoc();
    $old_qty = intval($row['quantity_received']);
    $item_id = isset($row['item_id']) ? intval($row['item_id']) : null;
    $stmt->close();

    $delta = $new_qty - $old_qty;

    // Update procurement_stock_in
    if ($new_date !== '') {
        // Accept date in YYYY-MM-DD
        $stmt_up = $conn->prepare("UPDATE procurement_stock_in SET quantity_received = ?, date_received = ? WHERE stock_in_id = ?");
        $stmt_up->bind_param('isi', $new_qty, $new_date, $stock_id);
    } else {
        $stmt_up = $conn->prepare("UPDATE procurement_stock_in SET quantity_received = ? WHERE stock_in_id = ?");
        $stmt_up->bind_param('ii', $new_qty, $stock_id);
    }
    if (!$stmt_up->execute()) throw new Exception('Failed to update stock_in: ' . $stmt_up->error);
    $stmt_up->close();

    // Adjust inventory master record using item_id
    if (!$item_id) {
        throw new Exception('Cannot update inventory: missing item reference');
    }

    $stmt_inv = $conn->prepare("SELECT item_id, quantity FROM procurement_inventory WHERE item_id = ? FOR UPDATE");
    $stmt_inv->bind_param('i', $item_id);
    $stmt_inv->execute();
    $res_inv = $stmt_inv->get_result();

    if ($res_inv->num_rows === 0) {
        // Inventory row for this item_id not found — this should not happen if stock-in was recorded correctly
        throw new Exception('Inventory record not found for item');
    } else {
        $inv = $res_inv->fetch_assoc();
        $current_qty = intval($inv['quantity']);
        $new_inventory_qty = $current_qty + $delta;
        if ($new_inventory_qty < 0) throw new Exception('Resulting inventory quantity would be negative');
        $stmt_update_inv = $conn->prepare("UPDATE procurement_inventory SET quantity = ?, last_updated = NOW() WHERE item_id = ?");
        $stmt_update_inv->bind_param('ii', $new_inventory_qty, $inv['item_id']);
        if (!$stmt_update_inv->execute()) throw new Exception('Failed to update inventory: ' . $stmt_update_inv->error);
        $stmt_update_inv->close();
    }
    $stmt_inv->close();

    $conn->commit();
    sendJson(true, 'Stock-in updated successfully');

} catch (Exception $e) {
    $conn->rollback();
    sendJson(false, $e->getMessage());
}

$conn->close();

?>
