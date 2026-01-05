<?php
/**
 * ========================= BACKEND: Project Phases =========================
 * Purpose: Handle CRUD operations AND Dropdown fetching for project phases.
 * Table: project_phases
 * ============================================================================ 
 */

require '../config.php'; // PDO connection
header('Content-Type: application/json');

try {
    // -------------------- 0. FETCH PHASES BY PROJECT (FOR DROPDOWNS) --------------------
    // This is the missing piece! It catches the request from tasks.php
    if (isset($_POST['project_id']) && !isset($_POST['phase_name']) && !isset($_POST['delete_id'])) {
        $project_id = $_POST['project_id'];
        
        $stmt = $conn->prepare("SELECT phase_id, phase_name FROM project_phases WHERE project_id = ? ORDER BY phase_name ASC");
        $stmt->execute([$project_id]);
        $phases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'phases' => $phases
        ]);
        exit; // Stop here so we don't accidentally try to 'save' this as a new phase
    }

    // -------------------- 1. DELETE PHASE --------------------
    if (isset($_POST['delete_id'])) {
        $phase_id = $_POST['delete_id'];

        // Clean up dependent tables first
        $stmt = $conn->prepare("DELETE FROM assignments WHERE phase_id = :phase_id");
        $stmt->execute([':phase_id' => $phase_id]);

        // Delete the phase
        $stmt = $conn->prepare("DELETE FROM project_phases WHERE phase_id = :phase_id");
        $stmt->execute([':phase_id' => $phase_id]);

        echo json_encode([
            'success' => $stmt->rowCount() > 0,
            'message' => $stmt->rowCount() > 0 ? 'Phase deleted successfully' : 'Phase not found'
        ]);
        exit;
    }

    // -------------------- 2. GET NEXT PHASE ID --------------------
    if (isset($_GET['get_next_id'])) {
        $stmt = $conn->prepare("SELECT phase_id FROM project_phases ORDER BY phase_id DESC LIMIT 1");
        $stmt->execute();
        $lastId = $stmt->fetch(PDO::FETCH_ASSOC)['phase_id'] ?? null;

        // Logic: PHS001 -> PHS002
        if ($lastId) {
            $num = intval(preg_replace('/[^0-9]/', '', $lastId)) + 1;
        } else {
            $num = 1;
        }
        $nextId = 'PHS' . str_pad($num, 3, '0', STR_PAD_LEFT);

        echo json_encode([
            'success' => true, 
            'next_id' => $nextId, 
            'message' => 'ID Generated' 
        ]);
        exit;
    }

    // -------------------- 3. FETCH SINGLE PHASE --------------------
    if (isset($_GET['fetch_id'])) {
        $phase_id = $_GET['fetch_id'];
        $stmt = $conn->prepare("
            SELECT ph.*, p.project_name 
            FROM project_phases ph
            LEFT JOIN projects p ON ph.project_id = p.project_id
            WHERE ph.phase_id = ?
            LIMIT 1
        ");
        $stmt->execute([$phase_id]);
        $phase = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => (bool)$phase,
            'phase' => $phase ?? null, 
            'message' => $phase ? 'Phase loaded' : 'Phase not found'
        ]);
        exit;
    }

    // -------------------- 4. ADD / EDIT PHASE --------------------
    // We check !isset($_POST['delete_id']) to ensure we aren't deleting
    // We check isset($_POST['phase_name']) to ensure we are actually saving a form, not just asking for a list
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phase_name'])) {
        
        $phase_id    = $_POST['phase_id'] ?? '';
        $phase_name  = $_POST['phase_name'] ?? '';
        
        // Handle empty values to prevent SQL errors
        $project_id  = !empty($_POST['project_id']) ? $_POST['project_id'] : null;
        $description = $_POST['description'] ?? '';
        $start_date  = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date    = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $status      = $_POST['status'] ?? 'Not Started';
        $budget      = !empty($_POST['budget']) ? $_POST['budget'] : 0.00;
        $priority    = $_POST['priority'] ?? 'Medium';

        // Check if exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM project_phases WHERE phase_id = ?");
        $stmt->execute([$phase_id]);
        $exists = $stmt->fetchColumn() > 0;

        if ($exists) {
            $sql = "UPDATE project_phases SET 
                        phase_name = :phase_name,
                        project_id = :project_id,
                        description = :description,
                        start_date = :start_date,
                        end_date = :end_date,
                        status = :status,
                        budget = :budget,
                        priority = :priority
                    WHERE phase_id = :phase_id";
            $msg = "Phase updated successfully";
        } else {
            $sql = "INSERT INTO project_phases 
                        (phase_id, project_id, phase_name, description, start_date, end_date, status, budget, priority)
                    VALUES 
                        (:phase_id, :project_id, :phase_name, :description, :start_date, :end_date, :status, :budget, :priority)";
            $msg = "Phase added successfully";
        }

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            ':phase_id'    => $phase_id,
            ':project_id'  => $project_id,
            ':phase_name'  => $phase_name,
            ':description' => $description,
            ':start_date'  => $start_date,
            ':end_date'    => $end_date,
            ':status'      => $status,
            ':budget'      => $budget,
            ':priority'    => $priority
        ]);

        if($result) {
            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            $err = $stmt->errorInfo();
            echo json_encode(['success' => false, 'message' => 'Database Error: ' . $err[2]]);
        }
        exit;
    }

    // -------------------- 5. FETCH ALL PHASES (DEFAULT) --------------------
    $stmt = $conn->prepare("
        SELECT 
            ph.phase_id,
            ph.phase_name,
            ph.description,
            ph.start_date,
            ph.end_date,
            ph.status,
            ph.project_id,
            p.project_name,
            IFNULL(ph.budget, 0) AS budget,
            IFNULL(ph.priority, 'Medium') AS priority
        FROM project_phases ph
        LEFT JOIN projects p ON ph.project_id = p.project_id
        ORDER BY ph.created_at DESC
    ");
    $stmt->execute();
    $phases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'phases' => $phases,
        'message' => 'Phases loaded'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>