<?php
/**
 * Reports API v1 - Procurement Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$db = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';
        $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

        if ($action === 'stats') {
            // Fetch stats
            $totalItems = $db->query("SELECT COUNT(*) FROM inventory")->fetchColumn();
            $lowStock = $db->query("SELECT COUNT(*) FROM inventory WHERE quantity <= 10")->fetchColumn();
            $totalValue = $db->query("SELECT SUM(quantity * unit_cost) FROM inventory")->fetchColumn();
            $pendingOrders = $db->query("SELECT COUNT(*) FROM purchase_orders WHERE status = 'PENDING'")->fetchColumn();

            echo json_encode([
                'success' => true,
                'data' => [
                    'total_items' => intval($totalItems),
                    'low_stock' => intval($lowStock),
                    'total_value' => floatval($totalValue),
                    'pending_orders' => intval($pendingOrders)
                ]
            ]);
        } else {
            echo json_encode(['success' => true, 'reports' => []]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
