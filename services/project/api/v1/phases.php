<?php
/**
 * ========================= API: Project Phases =========================
 * Purpose: Handle CRUD operations for project phases.
 * Table: project_phases
 * ============================================================================ 
 */

require_once __DIR__ . '/../../Database.php';
header('Content-Type: application/json');

$db = Database::getConnection();

try {
    $method = $_SERVER['REQUEST_METHOD'];

    // Handle JSON Input from API Gateway
    if (empty($_POST)) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (is_array($input)) {
            $_POST = $input;
        }
    }

    // -------------------- FETCH PHASES BY PROJECT (FOR DROPDOWNS) --------------------
    if ($method === 'POST' && isset($_POST['project_id']) && !isset($_POST['phase_name']) && !isset($_POST['delete_id'])) {
        $project_id = intval($_POST['project_id']);
        
        $stmt = $db->prepare("SELECT phase_id, phase_name FROM project_phases WHERE project_id = ? ORDER BY phase_name ASC");
        $stmt->execute([$project_id]);
        $phases = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'phases' => $phases
        ]);
        exit;
    }

    // -------------------- DELETE PHASE --------------------
    if ($method === 'POST' && isset($_POST['delete_id'])) {
        $phase_id = intval($_POST['delete_id']);

        // Delete dependent tasks first (same service)
        $db->prepare("DELETE FROM tasks WHERE phase_id = ?")->execute([$phase_id]);

        $stmt = $db->prepare("DELETE FROM project_phases WHERE phase_id = ?");
        $stmt->execute([$phase_id]);

        echo json_encode([
            'success' => $stmt->rowCount() > 0,
            'message' => $stmt->rowCount() > 0 ? 'Phase deleted successfully' : 'Phase not found'
        ]);
        exit;
    }

    // -------------------- GET NEXT PHASE ID --------------------
    if ($method === 'GET' && isset($_GET['get_next_id'])) {
        $stmt = $db->query("SELECT phase_id FROM project_phases ORDER BY phase_id DESC LIMIT 1");
        $row = $stmt->fetch();
        $nextId = ($row ? $row['phase_id'] : 0) + 1;

        echo json_encode([
            'success' => true, 
            'next_id' => $nextId
        ]);
        exit;
    }

    // -------------------- FETCH SINGLE PHASE --------------------
    if ($method === 'GET' && isset($_GET['fetch_id'])) {
        $phase_id = intval($_GET['fetch_id']);
        $stmt = $db->prepare("
            SELECT ph.*, p.project_name 
            FROM project_phases ph
            LEFT JOIN projects p ON ph.project_id = p.project_id
            WHERE ph.phase_id = ?
            LIMIT 1
        ");
        $stmt->execute([$phase_id]);
        $phase = $stmt->fetch();

        echo json_encode([
            'success' => (bool)$phase,
            'phase' => $phase ?? null, 
            'message' => $phase ? 'Phase loaded' : 'Phase not found'
        ]);
        exit;
    }

    // -------------------- ADD / EDIT PHASE --------------------
    if ($method === 'POST' && isset($_POST['phase_name'])) {
        $phase_id = isset($_POST['phase_id']) ? intval($_POST['phase_id']) : 0;
        $phase_name = trim($_POST['phase_name'] ?? '');
        $project_id = !empty($_POST['project_id']) ? intval($_POST['project_id']) : null;
        $description = trim($_POST['description'] ?? '');
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $status = $_POST['status'] ?? 'Not Started';
        
        // Calculate duration in days
        $duration = 0;
        if ($start_date && $end_date) {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $duration = $end->diff($start)->days;
        }

        // Simple validation for status (based on DB enum)
        $validStatuses = ['Not Started', 'In Progress', 'Completed'];
        if (!in_array($status, $validStatuses)) {
            $status = 'Not Started';
        }

        if ($phase_id > 0) {
            // UPDATE
            $sql = "UPDATE project_phases SET 
                        phase_name = ?,
                        project_id = ?,
                        description = ?,
                        start_date = ?,
                        end_date = ?,
                        duration = ?,
                        status = ?
                    WHERE phase_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$phase_name, $project_id, $description, $start_date, $end_date, $duration, $status, $phase_id]);
            $msg = "Phase updated successfully";
        } else {
            // INSERT
            $sql = "INSERT INTO project_phases 
                        (project_id, phase_name, description, start_date, end_date, duration, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$project_id, $phase_name, $description, $start_date, $end_date, $duration, $status]);
            $msg = "Phase added successfully";
        }

        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }

    // -------------------- FETCH ALL PHASES --------------------
    $stmt = $db->query("
        SELECT ph.*, p.project_name 
        FROM project_phases ph
        LEFT JOIN projects p ON ph.project_id = p.project_id
        ORDER BY ph.start_date DESC
    ");
    $phases = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'phases' => $phases
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
