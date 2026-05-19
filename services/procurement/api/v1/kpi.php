<?php
error_reporting(0);
ini_set('display_errors', 0);
/**
 * kpi.php - Procurement Analytics API v1
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$db = \Database::getConnection();

try {
    // Total Inventory Value
    $stmtVal = $db->query("SELECT SUM(quantity * 0) as total_value FROM inventory_items"); // Placeholder calculation
    
    // Recent Activities
    $stmtRec = $db->query("(SELECT 'Stock In' as type, item_id, quantity, received_date as date FROM stock_in)
                          UNION ALL
                          (SELECT 'Stock Out' as type, item_id, quantity, issue_date as date FROM stock_out)
                          ORDER BY date DESC LIMIT 10");
                          
    echo json_encode([
        'success' => true,
        'kpi' => [
            'total_items' => $db->query("SELECT COUNT(*) FROM inventory_items")->fetchColumn(),
            'active_suppliers' => $db->query("SELECT COUNT(*) FROM suppliers")->fetchColumn(),
            'pending_orders' => $db->query("SELECT COUNT(*) FROM purchase_orders WHERE status = 'Pending'")->fetchColumn(),
            'recent_activities' => $stmtRec->fetchAll()
        ]
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

