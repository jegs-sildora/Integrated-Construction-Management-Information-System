<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../../../config/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8mb4");

try {
    // ---------------------------------------------------------
    // CASE 1: Fetch Distinct Phase Names (for the dropdown/tabs)
    // ---------------------------------------------------------
    if (isset($_GET['project_id']) && (!isset($_GET['phase']) || trim($_GET['phase']) === '')) {
        $project_id = filter_var($_GET['project_id'], FILTER_VALIDATE_INT);

        if ($project_id === false || $project_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid project_id']);
            exit;
        }

        // First attempt: approved proposals only (simple, like create_order.php)
        $sql_phases = "SELECT DISTINCT pp.phase_id, pp.phase_name
                   FROM budget_proposals bp
                   JOIN icmis_project_phases pp ON bp.phase_id = pp.phase_id
                   WHERE bp.project_id = ? AND bp.status = 'APPROVED'
                   ORDER BY pp.phase_name ASC";

        $stmt = $conn->prepare($sql_phases);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Query prepare failed: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("i", $project_id);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Query execute failed: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        $result = $stmt->get_result();

        $phases = [];
        while ($row = $result->fetch_assoc()) {
            $phases[] = [
                'phase_id' => intval($row['phase_id']),
                'phase_name' => $row['phase_name']
            ];
        }

        // Fallback: if no approved proposals found, return all phases for the project
        $from_unapproved = false;
        if (count($phases) === 0) {
            $sql_fallback = "SELECT phase_id, phase_name
                             FROM icmis_project_phases
                             WHERE project_id = ?
                             ORDER BY start_date ASC";
            $stmt_fb = $conn->prepare($sql_fallback);
            if (!$stmt_fb) {
                echo json_encode(['success' => false, 'message' => 'Fallback query prepare failed: ' . $conn->error]);
                exit;
            }
            $stmt_fb->bind_param("i", $project_id);
            if (!$stmt_fb->execute()) {
                echo json_encode(['success' => false, 'message' => 'Fallback execute failed: ' . $stmt_fb->error]);
                $stmt_fb->close();
                exit;
            }
            $res_fb = $stmt_fb->get_result();
            $phases = [];
            while ($row = $res_fb->fetch_assoc()) {
                $phases[] = [
                    'phase_id' => intval($row['phase_id']),
                    'phase_name' => $row['phase_name']
                ];
            }
            $from_unapproved = true;
            $stmt_fb->close();
        }

        echo json_encode([
            'success' => true,
            'phases' => $phases,
            'count' => count($phases),
            'from_unapproved' => $from_unapproved
        ]);
        exit;
    }
    
    // ---------------------------------------------------------
    // CASE 2: Fetch Proposals for a Specific Phase (only when both params provided)
    // ---------------------------------------------------------
    if (isset($_GET['project_id']) && isset($_GET['phase']) && trim($_GET['phase']) !== '') {
        $project_id = intval($_GET['project_id']);
        $phase_name = trim($_GET['phase']); // This is the string "Phase 1: ..." from JS

        // QUERY UPDATE:
        // 1. JOIN 'icmis_project_phases' (pp) to filter by name and get dates
        // 2. JOIN 'budget_line_items' (bli) to count items
        // 3. JOIN 'icmis_users' to get the creator's full_name
        $sql = "SELECT 
                    bp.proposal_id,
                    bp.code,
                    bp.title,
                    bp.description,
                    pp.phase_name as phase,
                    pp.start_date as phase_start_date,
                    pp.end_date as phase_end_date,
                    bp.total_amount,
                    bp.status,
                    bp.created_at,
                    COALESCE(u.full_name, 'System') as user_name, 
                    (SELECT COUNT(*) FROM budget_line_items bli WHERE bli.proposal_id = bp.proposal_id) as line_item_count
                FROM budget_proposals bp
                JOIN icmis_project_phases pp ON bp.phase_id = pp.phase_id
                LEFT JOIN icmis_users u ON bp.created_by = u.user_id
                WHERE bp.project_id = ? 
                AND pp.phase_name = ? 
                AND UPPER(TRIM(bp.status)) = 'APPROVED'
                ORDER BY bp.created_at DESC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => "Query preparation failed: " . $conn->error]);
            exit;
        }

        $stmt->bind_param("is", $project_id, $phase_name);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Query execute failed: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        $result = $stmt->get_result();

        $proposals = [];
        while ($row = $result->fetch_assoc()) {
            $proposals[] = [
                'proposal_id' => intval($row['proposal_id']),
                'code' => $row['code'] ?? 'N/A',
                'title' => $row['title'],
                'description' => $row['description'],
                'phase' => $row['phase'],
                // Format dates safely
                'phase_start_date' => $row['phase_start_date'] ? date('M d, Y', strtotime($row['phase_start_date'])) : 'TBD',
                'phase_end_date' => $row['phase_end_date'] ? date('M d, Y', strtotime($row['phase_end_date'])) : 'TBD',
                'total_amount' => floatval($row['total_amount']),
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'user_name' => $row['user_name'],
                'line_item_count' => intval($row['line_item_count'])
            ];
        }

        echo json_encode([
            'success' => true,
            'proposals' => $proposals,
            'count' => count($proposals)
        ]);
        exit;
    }

} catch (Exception $e) {
    // Return JSON error without forcing HTTP 400 so client can read the message body
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}

$conn->close();
?>