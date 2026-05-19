<?php
/**
 * Reports API v1 - Procurement Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$db = \Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        $type = $_GET['type'] ?? '';
        $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 50;

        if ($type === 'movement') {
            $rows = [];

            $inWhere = '';
            $inParams = [];
            if ($project_id > 0) {
                $inWhere = 'WHERE po.project_id = ?';
                $inParams[] = $project_id;
            }
            $inSql = "SELECT si.date_received as movement_date, 'IN' as movement_type,
                             si.quantity_received as quantity, po.po_reference as reference,
                             i.item_name, NULL::int as issued_to_employee_id
                      FROM stock_in si
                      LEFT JOIN purchase_orders po ON si.po_id = po.po_id
                      LEFT JOIN inventory i ON si.item_id = i.item_id
                      $inWhere";

            $stmtIn = $db->prepare($inSql);
            $stmtIn->execute($inParams);
            $rows = array_merge($rows, $stmtIn->fetchAll());

            $outWhere = '';
            $outParams = [];
            if ($project_id > 0) {
                $outWhere = 'WHERE so.project_id = ?';
                $outParams[] = $project_id;
            }
            $outSql = "SELECT so.date_issued as movement_date, 'OUT' as movement_type,
                              so.quantity as quantity, NULL as reference,
                              i.item_name, so.issued_to_employee_id
                       FROM stock_out so
                       LEFT JOIN inventory i ON so.item_id = i.item_id
                       $outWhere";

            $stmtOut = $db->prepare($outSql);
            $stmtOut->execute($outParams);
            $rows = array_merge($rows, $stmtOut->fetchAll());

            usort($rows, function ($a, $b) {
                return strcmp($b['movement_date'], $a['movement_date']);
            });

            if ($limit > 0) {
                $rows = array_slice($rows, 0, $limit);
            }

            $formatted = [];
            foreach ($rows as $row) {
                $handler = '';
                if (!empty($row['issued_to_employee_id'])) {
                    $handler = 'Employee #' . $row['issued_to_employee_id'];
                }
                $formatted[] = [
                    'movement_date' => $row['movement_date'],
                    'type' => $row['movement_type'],
                    'quantity' => $row['quantity'],
                    'reference' => $row['reference'],
                    'item_name' => $row['item_name'],
                    'handler' => $handler,
                    'issued_to_employee_id' => $row['issued_to_employee_id']
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'rows' => $formatted
                ]
            ]);
            exit;
        }

        if ($action === 'stats') {
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
            exit;
        }

        echo json_encode(['success' => true, 'reports' => []]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

