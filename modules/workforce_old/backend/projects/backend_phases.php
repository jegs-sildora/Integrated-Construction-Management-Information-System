<?php
/**
 * ========================= BACKEND: Project Phases =========================
 * Purpose: Handle CRUD operations (Add/Edit/Delete/Fetch) for project phases.
 * Table: project_phases
 * Primary Key: phase_id
 * Related Table on Delete: assignments
 * Supports: Fetch all, fetch by project, fetch single, add/edit, delete, get next ID
 * ============================================================================ 
 */

require '../config.php'; // PDO connection
header('Content-Type: application/json');

try {
    // -------------------- DELETE PHASE --------------------
    if (isset($_POST['delete_id'])) {
        $phase_id = $_POST['delete_id'];

        // Delete dependent assignments first
        $stmt = $conn->prepare("DELETE FROM assignments WHERE phase_id = :phase_id");
        $stmt->execute([':phase_id' => $phase_id]);

        // Delete the phase itself
        $stmt = $conn->prepare("DELETE FROM project_phases WHERE phase_id = :phase_id");
        $stmt->execute([':phase_id' => $phase_id]);

        echo json_encode([
            'success' => $stmt->rowCount() > 0,
            'message' => $stmt->rowCount() > 0 ? 'Phase and related assignments deleted successfully' : 'Phase not found'
        ]);
        exit;
    }

    // -------------------- GET NEXT PHASE ID --------------------
    if (isset($_GET['get_next_id'])) {
        $stmt = $conn->prepare("SELECT phase_id FROM project_phases ORDER BY phase_id DESC LIMIT 1");
        $stmt->execute();
        $lastId = $stmt->fetch(PDO::FETCH_ASSOC)['phase_id'] ?? null;

        $num = $lastId ? intval(substr($lastId, 3)) + 1 : 1;
        $nextId = 'PHS' . str_pad($num, 3, '0', STR_PAD_LEFT);

        echo json_encode(['success' => true, 'phase_id' => $nextId]);
        exit;
    }

    // -------------------- FETCH SINGLE PHASE --------------------
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
            'message' => $phase ? 'Phase fetched successfully' : 'Phase not found'
        ]);
        exit;
    }

    // -------------------- FETCH PHASES BY PROJECT --------------------
    if (isset($_POST['project_id'])) {
        $project_id = $_POST['project_id'];
        $stmt = $conn->prepare("SELECT phase_id, phase_name FROM project_phases WHERE project_id = :project_id ORDER BY start_date ASC");
        $stmt->execute([':project_id' => $project_id]);
        $phases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'phases' => $phases
        ]);
        exit;
    }

    // -------------------- ADD / EDIT PHASE --------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id']) && !isset($_POST['project_id'])) {
        $phase_id    = $_POST['phase_id'] ?? '';
        $phase_name  = $_POST['phase_name'] ?? '';
        $project_id  = $_POST['project_id'] ?? '';
        $description = $_POST['description'] ?? '';
        $start_date  = $_POST['start_date'] ?? null;
        $end_date    = $_POST['end_date'] ?? null;
        $status      = $_POST['status'] ?? 'Not Started';
        $budget      = $_POST['budget'] ?? 0;
        $priority    = $_POST['priority'] ?? 'Medium';

        // Check if phase exists
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
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':phase_name'  => $phase_name,
                ':project_id'  => $project_id,
                ':description' => $description,
                ':start_date'  => $start_date,
                ':end_date'    => $end_date,
                ':status'      => $status,
                ':budget'      => $budget,
                ':priority'    => $priority,
                ':phase_id'    => $phase_id
            ]);

            echo json_encode(['success' => true, 'message' => 'Phase updated successfully']);
        } else {
            $sql = "INSERT INTO project_phases 
                        (phase_id, project_id, phase_name, description, start_date, end_date, status, budget, priority)
                    VALUES 
                        (:phase_id, :project_id, :phase_name, :description, :start_date, :end_date, :status, :budget, :priority)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
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

            echo json_encode(['success' => true, 'message' => 'Phase added successfully', 'phase_id' => $phase_id]);
        }
        exit;
    }

    // -------------------- FETCH ALL PHASES --------------------
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
            IFNULL(ph.actual_spent, 0) AS actual_spent,
            IFNULL(ph.priority, 'Medium') AS priority
        FROM project_phases ph
        LEFT JOIN projects p ON ph.project_id = p.project_id
        ORDER BY ph.start_date ASC
    ");
    $stmt->execute();
    $phases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'phases' => $phases
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
