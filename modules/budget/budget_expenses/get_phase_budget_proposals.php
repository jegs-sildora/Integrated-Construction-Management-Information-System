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
    if (!isset($_GET['project_id']) || !isset($_GET['phase'])) {
        throw new Exception('Missing required parameters');
    }

    $project_id = intval($_GET['project_id']);
    $phase_name = trim($_GET['phase']);

    // Try matching phase by name via icmis_project_phases
    $sql = "SELECT bp.proposal_id, bp.code, bp.title, bp.description, bp.total_amount, bp.status, bp.created_at, COALESCE(u.full_name, 'System') as user_name, pp.phase_name, pp.start_date as phase_start_date, pp.end_date as phase_end_date
            FROM budget_proposals bp
            LEFT JOIN icmis_project_phases pp ON bp.phase_id = pp.phase_id
            LEFT JOIN icmis_users u ON bp.created_by = u.user_id
            WHERE bp.project_id = ? AND bp.status = 'APPROVED' AND pp.phase_name LIKE ?
            ORDER BY bp.created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception('Failed to prepare query: ' . $conn->error);
    $phase_search = '%' . $phase_name . '%';
    $stmt->bind_param('is', $project_id, $phase_search);
    $stmt->execute();
    $result = $stmt->get_result();

    $proposals = [];
    while ($row = $result->fetch_assoc()) {
        $proposals[] = [
            'proposal_id' => intval($row['proposal_id']),
            'code' => $row['code'],
            'title' => $row['title'],
            'description' => $row['description'],
            'total_amount' => floatval($row['total_amount']),
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'user_name' => $row['user_name'],
            'phase_name' => $row['phase_name'],
            'phase_start_date' => $row['phase_start_date'],
            'phase_end_date' => $row['phase_end_date']
        ];
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'proposals' => $proposals,
        'count' => count($proposals)
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}

?>