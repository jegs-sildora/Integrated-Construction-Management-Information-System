<?php
/**
 * stockin.php - Unified Stock-In API v1
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

use Procurement\Database;

$db = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $stmt = $db->prepare("SELECT si.*, i.item_name, s.supplier_name 
                                FROM stock_in si 
                                JOIN inventory_items i ON si.item_id = i.item_id 
                                LEFT JOIN suppliers s ON si.supplier_id = s.supplier_id 
                                ORDER BY si.received_date DESC");
            $stmt->execute();
            echo json_encode(['success' => true, 'stockin' => $stmt->fetchAll()]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $db->beginTransaction();
            
            $sql = "INSERT INTO stock_in (item_id, supplier_id, quantity, unit_price, received_date, po_reference) 
                    VALUES (?, ?, ?, ?, ?, ?) RETURNING stock_in_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $input['item_id'], $input['supplier_id'], $input['quantity'], 
                $input['unit_price'], $input['received_date'] ?? date('Y-m-d'), $input['po_reference']
            ]);
            $id = $stmt->fetchColumn();

            // Update Inventory
            $upd = $db->prepare("UPDATE inventory_items SET quantity = quantity + ? WHERE item_id = ?");
            $upd->execute([$input['quantity'], $input['item_id']]);
            
            $db->commit();
            echo json_encode(['success' => true, 'stock_in_id' => $id]);
            break;

        case 'PUT':
            // Simple update logic
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['stock_in_id'] ?? null;
            if (!$id) throw new Exception("ID required");
            $sql = "UPDATE stock_in SET quantity = ?, unit_price = ? WHERE stock_in_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$input['quantity'], $input['unit_price'], $id]);
            echo json_encode(['success' => true]);
            break;
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
