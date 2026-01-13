<?php
// modules/procurement/php/fetch_stockin.php
header('Content-Type: application/json');
ini_set('display_errors', 0); 
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

try {
    // Use centralized config
    require_once __DIR__ . '/../../../config/config.php';
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }
    $conn->set_charset("utf8mb4");

    // Determine project_id from GET or session
    $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : (isset($_SESSION['current_project_id']) ? intval($_SESSION['current_project_id']) : 0);

    // Fetch stock in records with PO reference and user info
    $sql = "SELECT 
                si.stock_in_id, 
                si.po_id,
                po.po_reference, 
                COALESCE(i.item_name, '') as item_name,
                si.quantity_received, 
                DATE_FORMAT(si.date_received, '%b %d, %Y') as date_received,
                DATE_FORMAT(si.date_received, '%Y-%m-%d') as date_received_raw,
                u.full_name as received_by_name
            FROM procurement_stock_in si
            LEFT JOIN procurement_purchase_orders po ON si.po_id = po.po_id
            LEFT JOIN procurement_inventory i ON si.item_id = i.item_id
            LEFT JOIN icmis_users u ON po.created_by_user_id = u.user_id
            WHERE 1=1";
    if ($project_id > 0) {
        $sql .= " AND po.project_id = " . $project_id;
    }
    $sql .= " ORDER BY si.date_received DESC, si.stock_in_id DESC LIMIT 50";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("SQL Error: " . $conn->error);
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        if (empty($row['received_by_name'])) {
            $row['received_by_name'] = 'System';
        }
        $data[] = $row;
    }

    echo json_encode($data);
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>