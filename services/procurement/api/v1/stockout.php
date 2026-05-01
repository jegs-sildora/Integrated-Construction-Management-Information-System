<?php
/**
 * Stock Out API v1 - Procurement Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$db = Database::getConnection();

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db->beginTransaction();
        
        $stmt = $db->prepare("INSERT INTO stock_out (item_id, quantity, project_id, date_issued) VALUES (?, ?, ?, CURRENT_DATE)");
        $stmt->execute([$input['item_id'], $input['quantity'], $input['project_id']]);
        
        $stmt = $db->prepare("UPDATE inventory SET quantity = quantity - ? WHERE item_id = ?");
        $stmt->execute([$input['quantity'], $input['item_id']]);
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Stock out successful']);
    } else {
        $stmt = $db->query("SELECT * FROM stock_out ORDER BY stock_out_id DESC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
