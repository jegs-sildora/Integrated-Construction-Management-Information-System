<?php
/**
 * Stock Out API v1 - Procurement Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../Logger.php';

$db = Database::getConnection();

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db->beginTransaction();
        
        $item_id = intval($input['item_id']);
        $qty = floatval($input['quantity']);
        $project_id = intval($input['project_id'] ?? 0);
        
        $stmt = $db->prepare("INSERT INTO stock_out (item_id, quantity, project_id, date_issued) VALUES (?, ?, ?, CURRENT_DATE)");
        $stmt->execute([$item_id, $qty, $project_id ?: null]);
        $stock_out_id = intval($db->lastInsertId());
        
        $stmt = $db->prepare("UPDATE inventory SET quantity = quantity - ? WHERE item_id = ?");
        $stmt->execute([$qty, $item_id]);
        
        // Get item name for logging
        $stmtName = $db->prepare("SELECT item_name FROM inventory WHERE item_id = ?");
        $stmtName->execute([$item_id]);
        $name = $stmtName->fetchColumn();
        
        Logger::log('CREATE', 'Procurement', "Stock Out: Issued $qty units of $name (Project ID: $project_id)", $stock_out_id);
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Stock out successful']);
    } else {
        $stmt = $db->query("SELECT so.*, i.item_name FROM stock_out so LEFT JOIN inventory i ON so.item_id = i.item_id ORDER BY so.stock_out_id DESC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
