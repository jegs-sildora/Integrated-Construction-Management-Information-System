<?php
/**
 * ========================= API: Employees (for dropdowns) =========================
 * Purpose: Fetch employees for project manager and assignment dropdowns.
 * Table: workforce_employees
 * ============================================================================ 
 */

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
header('Content-Type: application/json');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8mb4");

try {
    // Fetch all active employees
    $sql = "SELECT employee_id, employee_code, first_name, last_name, status 
            FROM workforce_employees 
            WHERE status = 'Active'
            ORDER BY first_name, last_name ASC";
    $result = $conn->query($sql);
    
    $employees = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'employees' => $employees
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
