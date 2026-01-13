<?php
// modules/procurement/php/fetch_stockout.php
header('Content-Type: application/json; charset=utf-8');

$resp = [];
try {
    if (!file_exists(__DIR__ . '/../../../config/config.php')) {
        throw new Exception('Config not found');
    }
    require_once __DIR__ . '/../../../config/config.php';
    require_once __DIR__ . '/../../../config/database.php'; // provides $conn

    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!isset($conn) || !$conn) throw new Exception('DB connection not available');
    $conn->set_charset('utf8mb4');

    // Determine project_id from GET or session
    $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : (isset($_SESSION['current_project_id']) ? intval($_SESSION['current_project_id']) : 0);

    // FIXED SQL: Removed non-existent columns 'so.qty' and 'so.quantity_issued'
    $sql = "SELECT 
                so.stock_out_id, 
                so.item_id,
                COALESCE(pi.item_name, 'Unknown') as item_name, 
                so.quantity as quantity, 
                COALESCE(pi.unit, '') as unit, 
                so.issued_to_employee_id,
                CONCAT_WS(' ', we.first_name, we.last_name) as issued_to, 
                DATE_FORMAT(so.date_issued, '%b %d, %Y') as date_issued, 
                so.project_id,
                COALESCE(p.project_name, 'All') as project_name 
            FROM procurement_stock_out so 
            LEFT JOIN procurement_inventory pi ON so.item_id = pi.item_id 
            LEFT JOIN workforce_employees we ON so.issued_to_employee_id = we.employee_id
            LEFT JOIN icmis_projects p ON so.project_id = p.project_id
            WHERE 1=1";

    if ($project_id > 0) {
        $sql .= " AND so.project_id = " . $project_id;
    }
    
    $sql .= " ORDER BY so.date_issued DESC, so.stock_out_id DESC";

    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $resp[] = $row;
        }
        $result->free();
    } else {
        // Optional: Throw error if query fails so you can see it in logs
        throw new Exception($conn->error);
    }

    if (method_exists($conn, 'close')) $conn->close();

} catch (Throwable $e) {
    error_log('fetch_stockout error: ' . $e->getMessage());
    // On error, return empty array (or you could return an error object to debug)
    $resp = []; 
}

echo json_encode($resp);
?>