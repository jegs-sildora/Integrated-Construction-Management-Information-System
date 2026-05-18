<?php
/**
 * Inventory API v1 - Procurement Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../Logger.php';

$db = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Handle JSON input for POST/PUT/DELETE
$payload = [];
$raw = file_get_contents('php://input');
if (!empty($raw)) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}
if (!empty($_POST)) {
    $payload = array_merge($payload, $_POST);
}

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        $item_id = intval($_GET['id'] ?? $_GET['fetch_id'] ?? 0);
        $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
        $phase_id = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;

        if ($item_id > 0) {
            $stmt = $db->prepare("SELECT * FROM inventory WHERE item_id = ?");
            $stmt->execute([$item_id]);
            echo json_encode(['success' => true, 'item' => $stmt->fetch()]);
            exit;
        }

        $where = [];
        $params = [];
        if ($project_id > 0) {
            $where[] = "project_id = ?";
            $params[] = $project_id;
        }
        if ($phase_id > 0) {
            $where[] = "phase_id = ?";
            $params[] = $phase_id;
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        if ($action === 'dropdown') {
            $sql = "SELECT item_id, item_name, quantity, unit FROM inventory $whereSql ORDER BY item_name ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'inventory' => $stmt->fetchAll()]);
            exit;
        }

        $sql = "SELECT * FROM inventory $whereSql ORDER BY item_name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'inventory' => $stmt->fetchAll()]);
        exit;
    }

    if ($method === 'DELETE' || ($method === 'POST' && isset($payload['delete_id']))) {
        $item_id = intval($payload['delete_id'] ?? $payload['item_id'] ?? $_GET['id'] ?? $_GET['fetch_id'] ?? 0);
        if ($item_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
            exit;
        }

        $stmtName = $db->prepare("SELECT item_name FROM inventory WHERE item_id = ?");
        $stmtName->execute([$item_id]);
        $name = $stmtName->fetchColumn();

        $stmt = $db->prepare("DELETE FROM inventory WHERE item_id = ?");
        $stmt->execute([$item_id]);

        Logger::delete('Procurement', "Deleted inventory item: $name", $item_id);

        echo json_encode(['success' => true, 'message' => 'Item deleted']);
        exit;
    }

    if ($method === 'POST' || $method === 'PUT') {
        $item_id = intval($payload['item_id'] ?? $payload['id'] ?? 0);
        $name = trim($payload['item_name'] ?? '');
        $category = $payload['category'] ?? 'Uncategorized';
        $quantity = floatval($payload['quantity'] ?? 0);
        $unit = $payload['unit'] ?? '';
        $unit_cost = floatval($payload['unit_cost'] ?? 0);
        $project_id = isset($payload['project_id']) ? intval($payload['project_id']) : null;
        $phase_id = isset($payload['phase_id']) ? intval($payload['phase_id']) : null;

        if ($name === '') {
            echo json_encode(['success' => false, 'message' => 'Item name is required']);
            exit;
        }

        if ($item_id > 0) {
            $stmt = $db->prepare("UPDATE inventory SET item_name = ?, category = ?, quantity = ?, unit = ?, unit_cost = ?, project_id = ?, phase_id = ?, last_updated = CURRENT_TIMESTAMP WHERE item_id = ?");
            $stmt->execute([$name, $category, $quantity, $unit, $unit_cost, $project_id, $phase_id, $item_id]);

            Logger::update('Procurement', "Updated inventory item: $name (New Qty: $quantity)", $item_id);

            echo json_encode(['success' => true, 'message' => 'Item updated']);
        } else {
            $stmt = $db->prepare("INSERT INTO inventory (item_name, category, quantity, unit, unit_cost, project_id, phase_id) VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING item_id");
            $stmt->execute([$name, $category, $quantity, $unit, $unit_cost, $project_id, $phase_id]);
            $new_id = intval($stmt->fetchColumn());

            Logger::create('Procurement', "Created inventory item: $name (Qty: $quantity)", $new_id);

            echo json_encode(['success' => true, 'message' => 'Item created', 'id' => $new_id]);
        }
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

