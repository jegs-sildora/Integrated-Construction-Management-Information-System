<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

include __DIR__ . '/../connection.php';

try {
    // Check if this is a request for phases only
    if (isset($_GET['project_id']) && !isset($_GET['phase'])) {
        $project_id = intval($_GET['project_id']);
        
        if ($project_id <= 0) {
            throw new Exception('Invalid project_id');
        }
        
        // Fetch distinct phases from approved budget proposals
        $sql_phases = "SELECT DISTINCT phase 
                       FROM budget_proposals 
                       WHERE project_id = ? AND status = 'APPROVED'
                       ORDER BY phase";
        
        $stmt_phases = $conn->prepare($sql_phases);
        $stmt_phases->bind_param("i", $project_id);
        $stmt_phases->execute();
        $result_phases = $stmt_phases->get_result();
        
        $phases = [];
        while ($row = $result_phases->fetch_assoc()) {
            $phases[] = $row['phase'];
        }
        
        $stmt_phases->close();
        $conn->close();
        
        echo json_encode([
            'success' => true,
            'phases' => $phases,
            'count' => count($phases),
            'message' => count($phases) > 0 ? 'Phases loaded successfully' : 'No active phases found'
        ]);
        exit;
    }
    
    // Validate request parameters for proposals
    if (!isset($_GET['project_id']) || !isset($_GET['phase'])) {
        throw new Exception('Missing required parameters: project_id and phase');
    }

    $project_id = intval($_GET['project_id']);
    $phase = trim($_GET['phase']);

    if ($project_id <= 0) {
        throw new Exception('Invalid project_id');
    }

    // Fetch approved budget proposals for the specified project and phase
    $sql = "SELECT 
                bp.proposal_id,
                bp.code,
                bp.title,
                bp.description,
                bp.phase,
                bp.phase_start_date,
                bp.phase_end_date,
                bp.total_amount,
                bp.status,
                bp.created_at,
                bp.user_name,
                (SELECT COUNT(*) FROM budget_line_items bli WHERE bli.proposal_id = bp.proposal_id) as line_item_count
            FROM budget_proposals bp
            WHERE bp.project_id = ? 
            AND bp.phase = ?
            AND bp.status = 'APPROVED'
            ORDER BY bp.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $project_id, $phase);
    $stmt->execute();
    $result = $stmt->get_result();

    $proposals = [];
    while ($row = $result->fetch_assoc()) {
        $proposals[] = [
            'proposal_id' => intval($row['proposal_id']),
            'code' => $row['code'],
            'title' => $row['title'],
            'description' => $row['description'],
            'phase' => $row['phase'],
            'phase_start_date' => $row['phase_start_date'],
            'phase_end_date' => $row['phase_end_date'],
            'total_amount' => floatval($row['total_amount']),
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'user_name' => $row['user_name'],
            'line_item_count' => intval($row['line_item_count'])
        ];
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'proposals' => $proposals,
        'count' => count($proposals)
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
