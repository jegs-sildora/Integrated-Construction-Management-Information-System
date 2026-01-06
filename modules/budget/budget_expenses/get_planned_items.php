<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8mb4");

try {
    $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
    $phase = isset($_GET['phase']) ? trim($_GET['phase']) : '';
    $phase_id = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $proposal_ids = isset($_GET['proposal_ids']) ? $_GET['proposal_ids'] : '';

    // If no search term, return empty to prevent flooding
    if (empty($search)) {
        echo json_encode(['success' => true, 'items' => []]);
        exit;
    }

    // Base query: Select items from budget_line_items linked to approved proposals
    // We join budget_proposals to ensure we only get items from APPROVED budgets
    $sql = "SELECT 
                bli.item_name, 
                bli.category, 
                bli.quantity, 
                bli.unit_cost, 
                bp.code as proposal_code
            FROM budget_line_items bli
            JOIN budget_proposals bp ON bli.proposal_id = bp.proposal_id
            LEFT JOIN icmis_project_phases pp ON bp.phase_id = pp.phase_id
            WHERE 1=1 
            AND UPPER(TRIM(bp.status)) = 'APPROVED'";

    $params = [];
    $types = "";

    // Filter by Project
    if ($project_id > 0) {
        $sql .= " AND bp.project_id = ?";
        $params[] = $project_id;
        $types .= "i";
    }

    // Filter by Phase (Name or IDs)
    if (!empty($proposal_ids)) {
        // Safe injection of integers if proposal_ids are provided
        $ids = array_map('intval', explode(',', $proposal_ids));
        $ids_str = implode(',', $ids);
        $sql .= " AND bp.proposal_id IN ($ids_str)";
    } elseif ($phase_id > 0) {
        $sql .= " AND bp.phase_id = ?";
        $params[] = $phase_id;
        $types .= "i";
    } elseif (!empty($phase)) {
        $sql .= " AND pp.phase_name = ?";
        $params[] = $phase;
        $types .= "s";
    }

    // SEARCH LOGIC
    // Use wildcard search on the correct column (`item_name`).
    // The `budget_line_items` table does not have a `description` column,
    // so we search `item_name` case-insensitively to return matching results.
    $sql .= " AND UPPER(bli.item_name) LIKE UPPER(?)";
    $searchTerm = "%" . $search . "%";
    $params[] = $searchTerm;
    $types .= "s";

    // order by item name for consistent results
    $sql .= " ORDER BY bli.item_name ASC";

    $sql .= " LIMIT 10";

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception($conn->error);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'item_name' => $row['item_name'] ?? '',
            'category' => $row['category'] ?? '',
            'quantity' => isset($row['quantity']) ? (float)$row['quantity'] : 0,
            'unit_cost' => isset($row['unit_cost']) ? (float)$row['unit_cost'] : 0,
            'proposal_code' => $row['proposal_code'] ?? ''
        ];
    }

    echo json_encode(['success' => true, 'items' => $items]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>