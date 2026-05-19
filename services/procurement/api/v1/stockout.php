<?php
error_reporting(0);
ini_set('display_errors', 0);
/**
 * stockout.php - Unified Stock-Out API v1
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';



$db = \Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $stmt = $db->prepare("SELECT so.*, i.item_name 
                                FROM stock_out so 
                                JOIN inventory i ON so.item_id = i.item_id 
                                ORDER BY so.date_issued DESC");
            $stmt->execute();
            echo json_encode(['success' => true, 'stockout' => $stmt->fetchAll()]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $db->beginTransaction();
            
            // Check stock availability
            $check = $db->prepare("SELECT quantity FROM inventory WHERE item_id = ?");
            $check->execute([$input['item_id']]);
            $current = floatval($check->fetchColumn());
            
            if ($current < $input['quantity']) {
                throw new Exception("Insufficient stock. Available: $current");
            }

            $sql = "INSERT INTO stock_out (item_id, quantity, issued_to_employee_id, project_id, date_issued) 
                    VALUES (?, ?, ?, ?, ?) RETURNING stock_out_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $input['item_id'], $input['quantity'], 
                $input['issued_to_employee_id'] ?? $input['issued_to'], 
                $input['project_id'],
                $input['date_issued'] ?? $input['issue_date'] ?? date('Y-m-d')
            ]);
            $id = $stmt->fetchColumn();

            // Update Inventory
            $upd = $db->prepare("UPDATE inventory SET quantity = quantity - ? WHERE item_id = ?");
            $upd->execute([$input['quantity'], $input['item_id']]);
            
            $db->commit();
            echo json_encode(['success' => true, 'stock_out_id' => $id]);
            break;
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

