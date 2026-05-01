<?php
/**
 * Orders API v1 - Procurement Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$db = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            // Fetch single order and its items
            $stmt = $db->prepare("SELECT * FROM purchase_orders WHERE po_id = ?");
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
            $sql = "SELECT * FROM purchase_orders ORDER BY po_id DESC";
            $stmt = $db->query($sql);
            echo json_encode(['success' => true, 'orders' => $stmt->fetchAll()]);
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        if (isset($input['delete_id'])) {
            $stmt = $db->prepare("DELETE FROM purchase_orders WHERE po_id = ?");
            $stmt->execute([$input['delete_id']]);
            echo json_encode(['success' => true, 'message' => 'Order deleted']);
            exit;
        }

        // Add/Update logic
        $po_id = $input['po_id'] ?? 0;
        if ($po_id > 0) {
            $stmt = $db->prepare("UPDATE purchase_orders SET status = ? WHERE po_id = ?");
            $stmt->execute([$input['status'] ?? 'PENDING', $po_id]);
            echo json_encode(['success' => true, 'message' => 'Order updated']);
        } else {
            $stmt = $db->prepare("INSERT INTO purchase_orders (po_reference, project_id, phase_id, supplier_id, order_title, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['po_reference'] ?? 'PO-'.time(),
                $input['project_id'] ?? null,
                $input['phase_id'] ?? null,
                $input['supplier_id'] ?? null,
                $input['order_title'] ?? '',
                $input['status'] ?? 'PENDING'
            ]);
            echo json_encode(['success' => true, 'message' => 'Order created', 'id' => $db->lastInsertId()]);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
