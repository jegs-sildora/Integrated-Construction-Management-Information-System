<?php
header('Content-Type: application/json');
include __DIR__ . '/../connection.php';

try {
    // Validate required parameters
    if (!isset($_GET['project_id']) || !isset($_GET['phase']) || !isset($_GET['search'])) {
        throw new Exception('Missing required parameters');
    }

    $project_id = intval($_GET['project_id']);
    $phase = trim($_GET['phase']);
    $search = trim($_GET['search']);

    // Validate inputs
    if ($project_id <= 0) {
        throw new Exception('Invalid project ID');
    }

    // Fetch budget line items for the specified project and phase
    // that match the search term
    $sql = "SELECT DISTINCT 
                bli.item_name,
                bli.category,
                bli.quantity,
                bli.unit_cost,
                bp.code as proposal_code
            FROM budget_line_items bli
            INNER JOIN budget_proposals bp ON bli.proposal_id = bp.proposal_id
            WHERE bp.project_id = ? 
            AND bp.phase = ?
            AND bp.status = 'APPROVED'
            AND bli.item_name LIKE ?
            ORDER BY bli.item_name
            LIMIT 10";

    $stmt = $conn->prepare($sql);
    $search_param = '%' . $search . '%';
    $stmt->bind_param("iss", $project_id, $phase, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'item_name' => $row['item_name'],
            'category' => $row['category'],
            'quantity' => floatval($row['quantity']),
            'unit_cost' => floatval($row['unit_cost']),
            'proposal_code' => $row['proposal_code'],
            'display_text' => $row['item_name'] . ' (Planned: ' . number_format($row['quantity'], 0) . ' @ ₱' . number_format($row['unit_cost'], 2) . ')'
        ];
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'items' => $items
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
