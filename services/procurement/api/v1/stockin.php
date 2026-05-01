<?php
/**
 * Stock In API v1 - Procurement Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$db = Database::getConnection();

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db->beginTransaction();
        
        $stmt = $db->prepare("INSERT INTO stock_in (po_id, item_id, quantity_received, date_received) VALUES (?, ?, ?, CURRENT_DATE)");
        $stmt->execute([$input['po_id'], $input['item_id'], $input['quantity']]);
        
        $stmt = $db->prepare("UPDATE inventory SET quantity = quantity + ? WHERE item_id = ?");
        $stmt->execute([$input['quantity'], $input['item_id']]);
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Stock in successful']);
    } else {
        $stmt = $db->query("SELECT * FROM stock_in ORDER BY stock_in_id DESC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
