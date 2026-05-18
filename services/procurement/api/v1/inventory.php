<?php
/**
 * inventory.php - Unified Inventory API v1
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

use Procurement\Database;

$db = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id']) || isset($_GET['fetch_id'])) {
                $id = $_GET['id'] ?? $_GET['fetch_id'];
                $stmt = $db->prepare("SELECT * FROM inventory_items WHERE item_id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'item' => $stmt->fetch()]);
            } elseif (isset($_GET['action']) && $_GET['action'] === 'dropdown') {
                $stmt = $db->prepare("SELECT item_id, item_name, unit FROM inventory_items ORDER BY item_name ASC");
                $stmt->execute();
                echo json_encode(['success' => true, 'items' => $stmt->fetchAll()]);
            } else {
                $stmt = $db->prepare("SELECT * FROM inventory_items ORDER BY item_name ASC");
                $stmt->execute();
                echo json_encode(['success' => true, 'inventory' => $stmt->fetchAll()]);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $sql = "INSERT INTO inventory_items (item_name, category, unit, quantity, min_stock_level) VALUES (?, ?, ?, ?, ?) RETURNING item_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([$input['item_name'], $input['category'], $input['unit'], $input['quantity'] ?? 0, $input['min_stock_level'] ?? 0]);
            echo json_encode(['success' => true, 'item_id' => $stmt->fetchColumn()]);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
