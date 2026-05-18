<?php
/**
 * Stock Out API v1 - Procurement Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../Logger.php';

$db = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Handle JSON input
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
        $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 0;

        $where = [];
        $params = [];
        if ($project_id > 0) {
            $where[] = "so.project_id = ?";
            $params[] = $project_id;
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        $limitSql = $limit > 0 ? " LIMIT $limit" : '';

        $sql = "SELECT so.stock_out_id, so.item_id, so.quantity, so.project_id, so.issued_to_employee_id, so.date_issued,
                       i.item_name, i.unit
                FROM stock_out so
                LEFT JOIN inventory i ON so.item_id = i.item_id
                $whereSql
                ORDER BY so.stock_out_id DESC
                $limitSql";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        $db->beginTransaction();

        $item_id = intval($payload['item_id'] ?? $payload['stock_itemID'] ?? 0);
        $qty = floatval($payload['quantity'] ?? $payload['stock_quantity'] ?? 0);
        $project_id = intval($payload['project_id'] ?? $payload['stock_projectID'] ?? 0);
        $issued_to_employee_id = intval($payload['issued_to_employee_id'] ?? $payload['stock_issuedTo_id'] ?? 0);
        $date_issued = $payload['date_issued'] ?? null;

        if ($item_id <= 0 || $qty <= 0) {
            throw new Exception('Item and quantity are required');
        }

        // Validate stock availability
        $stmtQty = $db->prepare("SELECT quantity FROM inventory WHERE item_id = ?");
        $stmtQty->execute([$item_id]);
        $available = floatval($stmtQty->fetchColumn());
        if ($available < $qty) {
            throw new Exception('Insufficient stock available');
        }

        $stmt = $db->prepare("INSERT INTO stock_out (item_id, quantity, issued_to_employee_id, project_id, date_issued) VALUES (?, ?, ?, ?, COALESCE(?, CURRENT_DATE)) RETURNING stock_out_id");
        $stmt->execute([$item_id, $qty, $issued_to_employee_id ?: null, $project_id ?: null, $date_issued]);
        $stock_out_id = intval($stmt->fetchColumn());

        $stmt = $db->prepare("UPDATE inventory SET quantity = quantity - ?, last_updated = CURRENT_TIMESTAMP WHERE item_id = ?");
        $stmt->execute([$qty, $item_id]);

        $stmtName = $db->prepare("SELECT item_name FROM inventory WHERE item_id = ?");
        $stmtName->execute([$item_id]);
        $name = $stmtName->fetchColumn();

        Logger::log('CREATE', 'Procurement', "Stock Out: Issued $qty units of $name (Project ID: $project_id)", $stock_out_id);

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Stock out successful']);
        exit;
    }

    if ($method === 'DELETE') {
        $stock_out_id = intval($payload['stock_out_id'] ?? $_GET['id'] ?? $_GET['fetch_id'] ?? 0);
        if ($stock_out_id <= 0) throw new Exception('Invalid stock_out_id');

        $stmt = $db->prepare("SELECT item_id, quantity FROM stock_out WHERE stock_out_id = ?");
        $stmt->execute([$stock_out_id]);
        $existing = $stmt->fetch();
        if (!$existing) throw new Exception('Stock out record not found');

        $db->beginTransaction();
        $db->prepare("DELETE FROM stock_out WHERE stock_out_id = ?")->execute([$stock_out_id]);
        $db->prepare("UPDATE inventory SET quantity = quantity + ?, last_updated = CURRENT_TIMESTAMP WHERE item_id = ?")
           ->execute([floatval($existing['quantity']), intval($existing['item_id'])]);

        Logger::delete('Procurement', "Deleted stock out record #$stock_out_id", $stock_out_id);
        $db->commit();

        echo json_encode(['success' => true, 'message' => 'Stock out deleted']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

