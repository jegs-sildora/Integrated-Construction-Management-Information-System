<?php
/**
 * Inventory API v1 - Procurement Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$db = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT * FROM inventory WHERE item_id = ?");
            $stmt->execute([$_GET['id']]);
            echo json_encode(['success' => true, 'item' => $stmt->fetch()]);
        } else {
            $stmt = $db->query("SELECT * FROM inventory ORDER BY item_name ASC");
            echo json_encode(['success' => true, 'inventory' => $stmt->fetchAll()]);
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $item_id = $input['item_id'] ?? 0;
        if ($item_id > 0) {
            $stmt = $db->prepare("UPDATE inventory SET item_name = ?, category = ?, quantity = ?, unit = ? WHERE item_id = ?");
            $stmt->execute([$input['item_name'], $input['category'], $input['quantity'], $input['unit'], $item_id]);
            echo json_encode(['success' => true, 'message' => 'Item updated']);
        } else {
            $stmt = $db->prepare("INSERT INTO inventory (item_name, category, quantity, unit) VALUES (?, ?, ?, ?)");
            $stmt->execute([$input['item_name'], $input['category'] ?? 'Uncategorized', $input['quantity'] ?? 0, $input['unit'] ?? '']);
            echo json_encode(['success' => true, 'message' => 'Item created', 'id' => $db->lastInsertId()]);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
