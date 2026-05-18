<?php
/**
 * Orders API v1 - Procurement Service
 * Handles CRUD operations for Purchase Orders and their items.
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
        $id = intval($_GET['id'] ?? $_GET['fetch_id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("SELECT po.*, s.supplier_name FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id WHERE po.po_id = ?");
            $stmt->execute([$id]);
            $order = $stmt->fetch();
            if ($order) {
                $stmt = $db->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
                $stmt->execute([$id]);
                $order['items'] = $stmt->fetchAll();
                echo json_encode(['success' => true, 'order' => $order]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
            }
            exit;
        }

        $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
        $status = $_GET['status'] ?? '';
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 100;

        $where = [];
        $params = [];

        if ($project_id > 0) {
            $where[] = "po.project_id = ?";
            $params[] = $project_id;
        }
        if (!empty($status)) {
            $where[] = "po.status = ?";
            $params[] = $status;
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "SELECT po.*, s.supplier_name 
                FROM purchase_orders po 
                LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id 
                $whereSql 
                ORDER BY po.po_id DESC 
                LIMIT $limit";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'orders' => $stmt->fetchAll()]);
        exit;
    }

    if ($method === 'DELETE' || (isset($payload['action']) && $payload['action'] === 'delete')) {
        $po_id = intval($payload['po_id'] ?? $_GET['id'] ?? $_GET['fetch_id'] ?? 0);
        if ($po_id <= 0) throw new Exception("Invalid PO ID");

        $db->beginTransaction();
        $stmt = $db->prepare("DELETE FROM purchase_orders WHERE po_id = ?");
        $stmt->execute([$po_id]);
        $db->commit();

        Logger::delete('Procurement', "Deleted Purchase Order ID #$po_id", $po_id);

        echo json_encode(['success' => true, 'message' => 'Order deleted']);
        exit;
    }

    if ($method === 'POST' || $method === 'PUT') {
        $po_id = intval($payload['po_id'] ?? 0);
        $project_id = intval($payload['project_id'] ?? 0);
        $phase_id = intval($payload['phase_id'] ?? 0);
        $supplier_id = intval($payload['supplier_id'] ?? 0);
        $order_title = $payload['order_title'] ?? 'Untitled Order';
        $status = $payload['status'] ?? 'PENDING';
        $items = $payload['items'] ?? [];
        $user_id = intval($_SERVER['HTTP_X_USER_ID'] ?? $payload['user_id'] ?? 0);

        if ($project_id <= 0 || $supplier_id <= 0) {
            throw new Exception("Project and Supplier are required.");
        }

        $grand_total = 0;
        foreach ($items as $item) {
            $grand_total += (floatval($item['quantity'] ?? $item['qty'] ?? 0) * floatval($item['unit_cost'] ?? $item['price'] ?? 0));
        }

        $db->beginTransaction();

        if ($po_id > 0) {
            $stmt = $db->prepare("UPDATE purchase_orders SET project_id = ?, phase_id = ?, supplier_id = ?, order_title = ?, status = ?, total_amount = ? WHERE po_id = ?");
            $stmt->execute([$project_id, $phase_id ?: null, $supplier_id, $order_title, $status, $grand_total, $po_id]);

            $db->prepare("DELETE FROM purchase_order_items WHERE po_id = ?")->execute([$po_id]);
        } else {
            $year = date('Y');
            $stmtCount = $db->prepare("SELECT COUNT(*) as total FROM purchase_orders WHERE EXTRACT(YEAR FROM order_date) = ?");
            $stmtCount->execute([$year]);
            $next_num = intval($stmtCount->fetch()['total']) + 1;
            $po_reference = sprintf("PO-%s-%04d", $year, $next_num);

            $created_by = isset($_SERVER['HTTP_X_USER_ID']) ? intval($_SERVER['HTTP_X_USER_ID']) : ($user_id ?: null);

            $stmt = $db->prepare("INSERT INTO purchase_orders (po_reference, project_id, phase_id, supplier_id, order_title, total_amount, status, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING po_id");
            $stmt->execute([$po_reference, $project_id, $phase_id ?: null, $supplier_id, $order_title, $grand_total, $status, $created_by]);
            $po_id = $stmt->fetchColumn();
        }

        if (!empty($items)) {
            $stmtItem = $db->prepare("INSERT INTO purchase_order_items (po_id, item_name, quantity, unit_cost, total_cost) VALUES (?, ?, ?, ?, ?)");
            foreach ($items as $item) {
                $i_name = $item['item_name'] ?? $item['name'] ?? 'Unknown Item';
                $i_qty = floatval($item['quantity'] ?? $item['qty'] ?? 0);
                $i_price = floatval($item['unit_cost'] ?? $item['price'] ?? 0);
                $i_total = $i_qty * $i_price;
                $stmtItem->execute([$po_id, $i_name, $i_qty, $i_price, $i_total]);
            }
        }

        $db->commit();

        if ($po_id > 0 && !empty($payload['po_id'])) {
            Logger::update('Procurement', "Updated Purchase Order: $order_title", $po_id);
        } else {
            Logger::create('Procurement', "Created Purchase Order: $order_title", $po_id);
        }

        if (!isset($po_reference)) {
            $stmtRef = $db->prepare("SELECT po_reference FROM purchase_orders WHERE po_id = ?");
            $stmtRef->execute([$po_id]);
            $po_reference = $stmtRef->fetch()['po_reference'] ?? '';
        }

        echo json_encode([
            'success' => true,
            'message' => $po_id > 0 ? 'Order processed successfully' : 'Order created',
            'po_id' => $po_id,
            'po_reference' => $po_reference
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

