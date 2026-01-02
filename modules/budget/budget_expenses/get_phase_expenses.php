<?php
header('Content-Type: application/json');
include __DIR__ . '/../connection.php';

try {
    // Validate inputs
    if (!isset($_GET['project_id']) || !isset($_GET['phase'])) {
        throw new Exception('Missing required parameters');
    }

    $project_id = intval($_GET['project_id']);
    $phase = trim($_GET['phase']);

    // Validate phase
    $valid_phases = ['Phase 1: Mobilization', 'Phase 2: Structural', 'Phase 3: MEPFS', 'Phase 4: Finishing'];
    if (!in_array($phase, $valid_phases)) {
        throw new Exception('Invalid phase');
    }

    // Fetch expenses for the phase
    $sql = "SELECT e.expense_id, e.expense_date, e.category, e.description, e.amount, e.status,
                   s.name as supplier_name
            FROM expenses e
            LEFT JOIN suppliers s ON e.supplier_id = s.supplier_id
            WHERE e.project_id = ? AND e.phase = ?
            ORDER BY e.expense_date DESC, e.expense_id DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $project_id, $phase);
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
