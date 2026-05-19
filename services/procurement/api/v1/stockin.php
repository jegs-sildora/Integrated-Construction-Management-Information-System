<?php
/**
 * stockin.php - Unified Stock-In API v1
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';



$db = \Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $stmt = $db->prepare("SELECT si.*, i.item_name 
                                FROM stock_in si 
                                JOIN inventory i ON si.item_id = i.item_id 
                                ORDER BY si.date_received DESC");
            $stmt->execute();
            echo json_encode(['success' => true, 'stockin' => $stmt->fetchAll()]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $db->beginTransaction();
            
            $sql = "INSERT INTO stock_in (po_id, item_id, quantity_received, unit_cost, total_cost, date_received) 
                    VALUES (?, ?, ?, ?, ?, ?) RETURNING stock_in_id";
            $stmt = $db->prepare($sql);
            $qty = floatval($input['quantity_received'] ?? $input['quantity'] ?? 0);
            $unit_cost = floatval($input['unit_cost'] ?? $input['unit_price'] ?? 0);
            
            $stmt->execute([
                $input['po_id'], $input['item_id'], $qty, 
                $unit_cost, $qty * $unit_cost, $input['date_received'] ?? $input['received_date'] ?? date('Y-m-d')
            ]);
            $id = $stmt->fetchColumn();

            // Update Inventory
            $upd = $db->prepare("UPDATE inventory SET quantity = quantity + ? WHERE item_id = ?");
            $upd->execute([$qty, $input['item_id']]);
            
            $db->commit();
            echo json_encode(['success' => true, 'stock_in_id' => $id]);
            break;

        case 'PUT':
            // Simple update logic
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['stock_in_id'] ?? null;
            if (!$id) throw new Exception("ID required");
            $sql = "UPDATE stock_in SET quantity_received = ?, unit_cost = ? WHERE stock_in_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$input['quantity_received'], $input['unit_cost'], $id]);
            echo json_encode(['success' => true]);
            break;
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

