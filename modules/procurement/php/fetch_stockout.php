<?php
// modules/procurement/php/fetch_stockout.php
header('Content-Type: application/json; charset=utf-8');

// Use application's central config and database connection
// Path: from modules/procurement/php -> ../../../config/
$resp = [];
try {
    if (!file_exists(__DIR__ . '/../../../config/config.php')) {
        throw new Exception('Config not found');
    }
    require_once __DIR__ . '/../../../config/config.php';
    require_once __DIR__ . '/../../../config/database.php'; // provides $conn

    if (!isset($conn) || !$conn) throw new Exception('DB connection not available');
    $conn->set_charset('utf8mb4');

    $sql = "SELECT 
                so.stock_out_id, 
                pi.item_name, 
                so.quantity, 
                pi.unit, 
                CONCAT_WS(' ', we.first_name, we.last_name) as issued_to, 
                DATE_FORMAT(so.date_issued, '%b %d, %Y') as date_issued, 
                p.project_name 
            FROM procurement_stock_out so 
            JOIN procurement_inventory pi ON so.item_id = pi.item_id 
            LEFT JOIN workforce_employees we ON so.issued_to_employee_id = we.employee_id
            LEFT JOIN icmis_projects p ON so.project_id = p.project_id
            ORDER BY so.date_issued DESC, so.stock_out_id DESC";

    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $resp[] = $row;
        }
        $result->free();
    }

    // close only if database.php didn't intend to reuse connection elsewhere
    if (method_exists($conn, 'close')) $conn->close();

} catch (Throwable $e) {
    // Return empty array on error but keep status 200 with JSON
    error_log('fetch_stockout error: ' . $e->getMessage());
    $resp = [];
}

echo json_encode($resp);
