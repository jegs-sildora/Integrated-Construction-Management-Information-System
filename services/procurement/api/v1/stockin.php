<?php
/**
 * Stock In API v1 - Procurement Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../Logger.php';

$db = Database::getConnection();

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db->beginTransaction();
        
        $po_id = intval($input['po_id']);
        $item_id = intval($input['item_id']);
        $qty = floatval($input['quantity']);
        
        $stmt = $db->prepare("INSERT INTO stock_in (po_id, item_id, quantity_received, date_received) VALUES (?, ?, ?, CURRENT_DATE)");
        $stmt->execute([$po_id, $item_id, $qty]);
        $stock_in_id = intval($db->lastInsertId());
        
        $stmt = $db->prepare("UPDATE inventory SET quantity = quantity + ? WHERE item_id = ?");
        $stmt->execute([$qty, $item_id]);
        
        // Get item name for logging
        $stmtName = $db->prepare("SELECT item_name FROM inventory WHERE item_id = ?");
        $stmtName->execute([$item_id]);
        $name = $stmtName->fetchColumn();
        
        Logger::log('CREATE', 'Procurement', "Stock In: Received $qty units of $name (PO ID: $po_id)", $stock_in_id);
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Stock in successful']);
    } else {
        $stmt = $db->query("SELECT si.*, i.item_name FROM stock_in si LEFT JOIN inventory i ON si.item_id = i.item_id ORDER BY si.stock_in_id DESC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
