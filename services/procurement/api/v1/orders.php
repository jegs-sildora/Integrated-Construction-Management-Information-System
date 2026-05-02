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

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            // Fetch single order and its items
            $stmt = $db->prepare("SELECT po.*, s.supplier_name FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id WHERE po.po_id = ?");
            $stmt->execute([$_GET['id']]);
            $order = $stmt->fetch();
            if ($order) {
                $stmt = $db->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
                $stmt->execute([$_GET['id']]);
                $order['items'] = $stmt->fetchAll();
                echo json_encode(['success' => true, 'order' => $order]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
            }
        } else {
            // Fetch list
            $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
            $status = $_GET['status'] ?? '';
            
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
                    ORDER BY po.po_id DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'orders' => $stmt->fetchAll()]);
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        // Handle deletion
        if (isset($input['action']) && $input['action'] === 'delete') {
            $po_id = intval($input['po_id'] ?? 0);
            if ($po_id <= 0) throw new Exception("Invalid PO ID");

            $db->beginTransaction();
            // Items are deleted by CASCADE FK, but let's be explicit if needed or just trust FK
            $stmt = $db->prepare("DELETE FROM purchase_orders WHERE po_id = ?");
            $stmt->execute([$po_id]);
            $db->commit();

            Logger::delete('Procurement', "Deleted Purchase Order ID #$po_id", $po_id);

            echo json_encode(['success' => true, 'message' => 'Order deleted']);
            exit;
        }

        // Add/Update logic
        $po_id = intval($input['po_id'] ?? 0);
        $project_id = intval($input['project_id'] ?? 0);
        $phase_id = intval($input['phase_id'] ?? 0);
        $supplier_id = intval($input['supplier_id'] ?? 0);
        $order_title = $input['order_title'] ?? 'Untitled Order';
        $status = $input['status'] ?? 'PENDING';
        $items = $input['items'] ?? [];
        $user_id = intval($_SERVER['HTTP_X_USER_ID'] ?? $input['user_id'] ?? 0);

        if ($project_id <= 0 || $supplier_id <= 0) {
            throw new Exception("Project and Supplier are required.");
        }

        // Calculate total
        $grand_total = 0;
        foreach ($items as $item) {
            $grand_total += (floatval($item['quantity'] ?? $item['qty'] ?? 0) * floatval($item['unit_cost'] ?? $item['price'] ?? 0));
        }

        $db->beginTransaction();

        if ($po_id > 0) {
            // UPDATE
            $stmt = $db->prepare("UPDATE purchase_orders SET project_id = ?, phase_id = ?, supplier_id = ?, order_title = ?, status = ?, total_amount = ? WHERE po_id = ?");
            $stmt->execute([$project_id, $phase_id ?: null, $supplier_id, $order_title, $status, $grand_total, $po_id]);

            // Clear and Re-insert items
            $db->prepare("DELETE FROM purchase_order_items WHERE po_id = ?")->execute([$po_id]);
        } else {
            // CREATE
            // Generate PO Reference: PO-YYYY-XXXX
            $year = date('Y');
            $stmtCount = $db->prepare("SELECT COUNT(*) as total FROM purchase_orders WHERE EXTRACT(YEAR FROM order_date) = ?");
            $stmtCount->execute([$year]);
            $next_num = intval($stmtCount->fetch()['total']) + 1;
            $po_reference = sprintf("PO-%s-%04d", $year, $next_num);

            // Use User ID from Gateway header if available
            $created_by = isset($_SERVER['HTTP_X_USER_ID']) ? intval($_SERVER['HTTP_X_USER_ID']) : ($user_id ?: null);

            $stmt = $db->prepare("INSERT INTO purchase_orders (po_reference, project_id, phase_id, supplier_id, order_title, total_amount, status, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$po_reference, $project_id, $phase_id ?: null, $supplier_id, $order_title, $grand_total, $status, $created_by]);
            $po_id = $db->lastInsertId();
        }

        // Insert Items
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
        
        // Audit Logging
        if (isset($input['po_id']) && intval($input['po_id']) > 0) {
            Logger::update('Procurement', "Updated Purchase Order: $order_title ($po_reference)", $po_id);
        } else {
            Logger::create('Procurement', "Created Purchase Order: $order_title ($po_reference)", $po_id);
        }
        
        // Fetch the reference for the response if it was an update
        if (!isset($po_reference)) {
            $stmtRef = $db->prepare("SELECT po_reference FROM purchase_orders WHERE po_id = ?");
            $stmtRef->execute([$po_id]);
            $po_reference = $stmtRef->fetch()['po_reference'];
        }

        echo json_encode([
            'success' => true, 
            'message' => $po_id > 0 ? 'Order processed successfully' : 'Order created', 
            'po_id' => $po_id,
            'po_reference' => $po_reference
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
