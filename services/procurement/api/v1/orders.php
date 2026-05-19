<?php
error_reporting(0);
ini_set('display_errors', 0);
/**
 * orders.php - Unified Orders API v1
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';



$db = \Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id']) || isset($_GET['fetch_id'])) {
                $id = $_GET['id'] ?? $_GET['fetch_id'];
                $stmt = $db->prepare("SELECT o.*, s.supplier_name 
                                    FROM purchase_orders o 
                                    LEFT JOIN suppliers s ON o.supplier_id = s.supplier_id 
                                    WHERE o.po_id = ?");
                $stmt->execute([$id]);
                $order = $stmt->fetch();
                
                if ($order) {
                    $stmtItems = $db->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
                    $stmtItems->execute([$id]);
                    $order['items'] = $stmtItems->fetchAll();
                }

                echo json_encode(['success' => (bool)$order, 'order' => $order]);
            } elseif (isset($_GET['action']) && $_GET['action'] === 'approved') {
                $stmt = $db->prepare("SELECT po_id, po_reference FROM purchase_orders WHERE status = 'APPROVED' ORDER BY po_reference ASC");
                $stmt->execute();
                echo json_encode(['success' => true, 'orders' => $stmt->fetchAll()]);
            } else {
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
                $stmt = $db->prepare("SELECT o.*, s.supplier_name FROM purchase_orders o LEFT JOIN suppliers s ON o.supplier_id = s.supplier_id ORDER BY o.po_id DESC LIMIT ?");
                $stmt->execute([$limit]);
                echo json_encode(['success' => true, 'orders' => $stmt->fetchAll()]);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $project_id = intval($input['project_id'] ?? 0);

            if ($project_id <= 0) throw new Exception("Valid Project ID required");

            // Inter-service Validation
            $project_service_base = rtrim(getenv('PROJECT_SERVICE_URL') ?: 'http://project-service', '/');
            $url = $project_service_base . '/api/v1/projects.php?fetch_id=' . $project_id;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $res = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            
            if ($res === false || $info['http_code'] !== 200) {
                throw new Exception("Inter-service validation failed for $url");
            }
            $proj_data = json_decode($res, true);
            if (!isset($proj_data['success']) || !$proj_data['success']) {
                throw new Exception("Validation Error: " . ($proj_data['message'] ?? 'Invalid Project ID'));
            }

            $db->beginTransaction();
            
            $sql = "INSERT INTO purchase_orders (po_reference, supplier_id, project_id, order_date, total_amount, status) 
                    VALUES (?, ?, ?, ?, ?, ?) RETURNING po_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $input['po_reference'], $input['supplier_id'], $project_id, 
                $input['order_date'] ?? date('Y-m-d'), $input['total_amount'], $input['status'] ?? 'PENDING'
            ]);
            $poId = $stmt->fetchColumn();

            if (isset($input['items']) && is_array($input['items'])) {
                $itemSql = "INSERT INTO purchase_order_items (po_id, item_name, quantity, unit_cost, total_cost) VALUES (?, ?, ?, ?, ?)";
                $itemStmt = $db->prepare($itemSql);
                foreach ($input['items'] as $item) {
                    $itemStmt->execute([$poId, $item['item_name'] ?? $item['name'], $item['quantity'], $item['unit_cost'] ?? $item['unit_price'], ($item['quantity'] * ($item['unit_cost'] ?? $item['unit_price']))]);
                }
            }
            
            $db->commit();
            echo json_encode(['success' => true, 'po_id' => $poId]);
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['po_id'] ?? null;
            if (!$id) throw new Exception("Order ID required");

            $sql = "UPDATE purchase_orders SET supplier_id = ?, total_amount = ?, status = ? WHERE po_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$input['supplier_id'], $input['total_amount'], $input['status'], $id]);
            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception("Order ID required");
            $stmt = $db->prepare("DELETE FROM purchase_orders WHERE po_id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

