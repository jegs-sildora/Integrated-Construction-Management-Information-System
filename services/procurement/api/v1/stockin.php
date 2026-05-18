<?php
/**
 * Stock In API v1 - Procurement Service
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
            $where[] = "po.project_id = ?";
            $params[] = $project_id;
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        $limitSql = $limit > 0 ? " LIMIT $limit" : '';

        $sql = "SELECT si.stock_in_id, si.po_id, po.po_reference, si.item_id, i.item_name, i.unit,
                       si.quantity_received, si.unit_cost, si.total_cost, si.date_received
                FROM stock_in si
                LEFT JOIN purchase_orders po ON si.po_id = po.po_id
                LEFT JOIN inventory i ON si.item_id = i.item_id
                $whereSql
                ORDER BY si.stock_in_id DESC
                $limitSql";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['date_received_raw'] = $row['date_received'];
        }

        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        $db->beginTransaction();

        $po_id = intval($payload['po_id'] ?? 0);
        $items = $payload['items'] ?? [];
        $date_received = $payload['date_received'] ?? null;

        if ($po_id <= 0) {
            throw new Exception('PO ID is required');
        }

        // Fetch PO context for project/phase mapping
        $stmtPo = $db->prepare("SELECT project_id, phase_id FROM purchase_orders WHERE po_id = ? LIMIT 1");
        $stmtPo->execute([$po_id]);
        $po = $stmtPo->fetch();
        if (!$po) {
            throw new Exception('Purchase Order not found');
        }

        $project_id = intval($payload['project_id'] ?? $po['project_id'] ?? 0);
        $phase_id = intval($payload['phase_id'] ?? $po['phase_id'] ?? 0);

        // Bulk receive from PO items
        if (is_array($items) && count($items) > 0) {
            $insertStmt = $db->prepare("INSERT INTO stock_in (po_id, item_id, quantity_received, unit_cost, total_cost, date_received)
                                        VALUES (?, ?, ?, ?, ?, COALESCE(?, CURRENT_DATE))");

            foreach ($items as $item) {
                $po_item_id = intval($item['item_db_id'] ?? $item['po_item_id'] ?? 0);
                $qty = floatval($item['received_qty'] ?? $item['quantity_received'] ?? $item['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $item_name = trim($item['item_name'] ?? '');
                $unit_cost = floatval($item['unit_cost'] ?? 0);

                $inventory_item_id = 0;
                if ($po_item_id > 0) {
                    $stmtItem = $db->prepare("SELECT inventory_item_id, item_name, unit_cost FROM purchase_order_items WHERE po_item_id = ? LIMIT 1");
                    $stmtItem->execute([$po_item_id]);
                    if ($row = $stmtItem->fetch()) {
                        $inventory_item_id = intval($row['inventory_item_id'] ?? 0);
                        if ($item_name === '') $item_name = $row['item_name'] ?? '';
                        if ($unit_cost <= 0) $unit_cost = floatval($row['unit_cost'] ?? 0);
                    }
                }

                if ($inventory_item_id <= 0 && $item_name !== '') {
                    $stmtFind = $db->prepare("SELECT item_id FROM inventory WHERE item_name = ?" . ($project_id > 0 ? " AND project_id = ?" : "") . " LIMIT 1");
                    $params = [$item_name];
                    if ($project_id > 0) $params[] = $project_id;
                    $stmtFind->execute($params);
                    $inventory_item_id = intval($stmtFind->fetchColumn());
                }

                if ($inventory_item_id <= 0) {
                    $stmtInv = $db->prepare("INSERT INTO inventory (item_name, category, quantity, unit, unit_cost, project_id, phase_id) VALUES (?, ?, 0, ?, ?, ?, ?) RETURNING item_id");
                    $stmtInv->execute([
                        $item_name !== '' ? $item_name : 'Uncategorized Item',
                        'Uncategorized',
                        $item['unit'] ?? '',
                        $unit_cost,
                        $project_id > 0 ? $project_id : null,
                        $phase_id > 0 ? $phase_id : null
                    ]);
                    $inventory_item_id = intval($stmtInv->fetchColumn());
                }

                // Update inventory quantity and cost
                $stmtUpdateInv = $db->prepare("UPDATE inventory SET quantity = quantity + ?, unit_cost = CASE WHEN ? > 0 THEN ? ELSE unit_cost END, last_updated = CURRENT_TIMESTAMP WHERE item_id = ?");
                $stmtUpdateInv->execute([$qty, $unit_cost, $unit_cost, $inventory_item_id]);

                $total_cost = $unit_cost * $qty;
                $insertStmt = $db->prepare("INSERT INTO stock_in (po_id, item_id, quantity_received, unit_cost, total_cost, date_received)
                                            VALUES (?, ?, ?, ?, ?, COALESCE(?, CURRENT_DATE)) RETURNING stock_in_id");
                $insertStmt->execute([$po_id, $inventory_item_id, $qty, $unit_cost, $total_cost, $date_received]);
                $stock_in_id = intval($insertStmt->fetchColumn());

                if ($po_item_id > 0) {
                    $db->prepare("UPDATE purchase_order_items SET inventory_item_id = ? WHERE po_item_id = ?")
                       ->execute([$inventory_item_id, $po_item_id]);
                }

                Logger::log('CREATE', 'Procurement', "Stock In: Received $qty units of $item_name (PO ID: $po_id)", $stock_in_id);
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Stock in successful']);
            exit;
        }

        // Single-item fallback
        $item_id = intval($payload['item_id'] ?? 0);
        $qty = floatval($payload['quantity'] ?? 0);

        if ($item_id <= 0 || $qty <= 0) {
            throw new Exception('Item and quantity are required');
        }

        $unit_cost = floatval($payload['unit_cost'] ?? 0);
        $total_cost = $unit_cost * $qty;

        $stmt = $db->prepare("INSERT INTO stock_in (po_id, item_id, quantity_received, unit_cost, total_cost, date_received) VALUES (?, ?, ?, ?, ?, COALESCE(?, CURRENT_DATE))");
        $stmt->execute([$po_id, $item_id, $qty, $unit_cost, $total_cost, $date_received]);
        $stock_in_id = intval($db->lastInsertId());

        $stmt = $db->prepare("UPDATE inventory SET quantity = quantity + ?, last_updated = CURRENT_TIMESTAMP WHERE item_id = ?");
        $stmt->execute([$qty, $item_id]);

        $stmtName = $db->prepare("SELECT item_name FROM inventory WHERE item_id = ?");
        $stmtName->execute([$item_id]);
        $name = $stmtName->fetchColumn();

        Logger::log('CREATE', 'Procurement', "Stock In: Received $qty units of $name (PO ID: $po_id)", $stock_in_id);

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Stock in successful']);
        exit;
    }

    if ($method === 'PUT') {
        $stock_in_id = intval($payload['stock_in_id'] ?? $_GET['id'] ?? $_GET['fetch_id'] ?? 0);
        if ($stock_in_id <= 0) throw new Exception('Invalid stock_in_id');

        $stmt = $db->prepare("SELECT item_id, quantity_received FROM stock_in WHERE stock_in_id = ?");
        $stmt->execute([$stock_in_id]);
        $existing = $stmt->fetch();
        if (!$existing) throw new Exception('Stock in record not found');

        $new_qty = floatval($payload['quantity_received'] ?? $existing['quantity_received']);
        $date_received = $payload['date_received'] ?? null;
        $delta = $new_qty - floatval($existing['quantity_received']);

        $db->beginTransaction();

        $stmt = $db->prepare("UPDATE stock_in SET quantity_received = ?, date_received = COALESCE(?, date_received) WHERE stock_in_id = ?");
        $stmt->execute([$new_qty, $date_received, $stock_in_id]);

        if ($delta != 0) {
            $stmt = $db->prepare("UPDATE inventory SET quantity = quantity + ?, last_updated = CURRENT_TIMESTAMP WHERE item_id = ?");
            $stmt->execute([$delta, $existing['item_id']]);
        }

        Logger::update('Procurement', "Updated stock in record #$stock_in_id", $stock_in_id);

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Stock in updated']);
        exit;
    }

    if ($method === 'DELETE') {
        $stock_in_id = intval($payload['stock_in_id'] ?? $_GET['id'] ?? $_GET['fetch_id'] ?? 0);
        if ($stock_in_id <= 0) throw new Exception('Invalid stock_in_id');

        $stmt = $db->prepare("SELECT item_id, quantity_received FROM stock_in WHERE stock_in_id = ?");
        $stmt->execute([$stock_in_id]);
        $existing = $stmt->fetch();
        if (!$existing) throw new Exception('Stock in record not found');

        $db->beginTransaction();
        $db->prepare("DELETE FROM stock_in WHERE stock_in_id = ?")->execute([$stock_in_id]);
        $db->prepare("UPDATE inventory SET quantity = quantity - ?, last_updated = CURRENT_TIMESTAMP WHERE item_id = ?")
           ->execute([floatval($existing['quantity_received']), intval($existing['item_id'])]);

        Logger::delete('Procurement', "Deleted stock in record #$stock_in_id", $stock_in_id);
        $db->commit();

        echo json_encode(['success' => true, 'message' => 'Stock in deleted']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

