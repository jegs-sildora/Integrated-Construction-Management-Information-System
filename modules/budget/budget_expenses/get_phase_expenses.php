<?php
header('Content-Type: application/json');

// Include config for database connection
require_once __DIR__ . '/../../../config/config.php';

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset("utf8mb4");

try {
    // Validate inputs
    if (!isset($_GET['project_id']) || !isset($_GET['phase'])) {
        throw new Exception('Missing required parameters');
    }

    $project_id = intval($_GET['project_id']);
    $phase_name = trim($_GET['phase']);

    // Fetch expenses for the phase by joining with icmis_project_phases
    $sql = "SELECT e.expense_id, e.expense_date, e.category, e.description, e.amount, e.status,
                   s.supplier_name,
                   pp.phase_name as phase
            FROM budget_expenses e
            LEFT JOIN procurement_suppliers s ON e.supplier_id = s.supplier_id
            LEFT JOIN icmis_project_phases pp ON e.phase_id = pp.phase_id
            WHERE e.project_id = ? AND pp.phase_name = ?
            ORDER BY e.expense_date DESC, e.expense_id DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }
    
    $stmt->bind_param("is", $project_id, $phase_name);
    $stmt->execute();
    $result = $stmt->get_result();

    $expenses = [];
    while ($row = $result->fetch_assoc()) {
        $expenses[] = [
            'expense_id' => $row['expense_id'],
            'expense_date' => $row['expense_date'],
            'category' => $row['category'],
            'description' => $row['description'],
            'amount' => floatval($row['amount']),
            'status' => $row['status'],
            'supplier_name' => $row['supplier_name']
        ];
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'expenses' => $expenses,
        'count' => count($expenses)
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
