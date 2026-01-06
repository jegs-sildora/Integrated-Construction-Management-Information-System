<?php
/**
 * ========================= API: Project Phases =========================
 * Purpose: Handle CRUD operations for project phases.
 * Table: icmis_project_phases
 * ============================================================================ 
 */

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
header('Content-Type: application/json');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8mb4");

try {
    // -------------------- FETCH PHASES BY PROJECT (FOR DROPDOWNS) --------------------
    if (isset($_POST['project_id']) && !isset($_POST['phase_name']) && !isset($_POST['delete_id'])) {
        $project_id = intval($_POST['project_id']);
        
        $stmt = $conn->prepare("SELECT phase_id, phase_name FROM icmis_project_phases WHERE project_id = ? ORDER BY phase_name ASC");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $phases = [];
        while ($row = $result->fetch_assoc()) {
            $phases[] = $row;
        }
        $stmt->close();

        echo json_encode([
            'success' => true,
            'phases' => $phases
        ]);
        exit;
    }

    // -------------------- DELETE PHASE --------------------
    if (isset($_POST['delete_id'])) {
        $phase_id = intval($_POST['delete_id']);

        // Delete dependent tasks first
        $conn->query("DELETE FROM icmis_tasks WHERE phase_id = $phase_id");

        $stmt = $conn->prepare("DELETE FROM icmis_project_phases WHERE phase_id = ?");
        $stmt->bind_param("i", $phase_id);
        $stmt->execute();

        echo json_encode([
            'success' => $stmt->affected_rows > 0,
            'message' => $stmt->affected_rows > 0 ? 'Phase deleted successfully' : 'Phase not found'
        ]);
        $stmt->close();
        exit;
    }

    // -------------------- GET NEXT PHASE ID --------------------
    if (isset($_GET['get_next_id'])) {
        $result = $conn->query("SELECT phase_id FROM icmis_project_phases ORDER BY phase_id DESC LIMIT 1");
        $row = $result->fetch_assoc();
        $nextId = ($row ? $row['phase_id'] : 0) + 1;

        echo json_encode([
            'success' => true, 
            'next_id' => $nextId
        ]);
        exit;
    }

    // -------------------- FETCH SINGLE PHASE --------------------
    if (isset($_GET['fetch_id'])) {
        $phase_id = intval($_GET['fetch_id']);
        $stmt = $conn->prepare("
            SELECT ph.*, p.project_name 
            FROM icmis_project_phases ph
            LEFT JOIN icmis_projects p ON ph.project_id = p.project_id
            WHERE ph.phase_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $phase_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $phase = $result->fetch_assoc();
        $stmt->close();

        echo json_encode([
            'success' => (bool)$phase,
            'phase' => $phase ?? null, 
            'message' => $phase ? 'Phase loaded' : 'Phase not found'
        ]);
        exit;
    }

    // -------------------- ADD / EDIT PHASE --------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phase_name'])) {
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

        // Validate and normalize `status` to match DB column definition
        $colQuery = "SELECT COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . $conn->real_escape_string(DB_NAME) . "' AND TABLE_NAME = 'icmis_project_phases' AND COLUMN_NAME = 'status' LIMIT 1";
        $colRes = $conn->query($colQuery);
        if ($colRes && $colInfo = $colRes->fetch_assoc()) {
            $colType = $colInfo['COLUMN_TYPE'];
            if (strpos($colType, 'enum(') === 0) {
                preg_match_all("/'([^']+)'/", $colType, $m);
                $enumVals = $m[1] ?? [];
                if (!in_array($status, $enumVals, true)) {
                    if (in_array('Not Started', $enumVals, true)) {
                        $status = 'Not Started';
                    } elseif (!empty($enumVals)) {
                        $status = $enumVals[0];
                    }
                }
            } else {
                $max = (int)$colInfo['CHARACTER_MAXIMUM_LENGTH'];
                if ($max > 0 && strlen($status) > $max) {
                    $status = substr($status, 0, $max);
                }
            }
        }

        // Check if exists
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM icmis_project_phases WHERE phase_id = ?");
        $stmt->bind_param("i", $phase_id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc()['cnt'] > 0;
        $stmt->close();

        if ($exists) {
            $sql = "UPDATE icmis_project_phases SET 
                        phase_name = ?,
                        project_id = ?,
                        description = ?,
                        start_date = ?,
                        end_date = ?,
                        duration = ?,
                        status = ?
                    WHERE phase_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sisssisi", $phase_name, $project_id, $description, $start_date, $end_date, $duration, $status, $phase_id);
            $msg = "Phase updated successfully";
        } else {
            $sql = "INSERT INTO icmis_project_phases 
                        (project_id, phase_name, description, start_date, end_date, duration, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssis", $project_id, $phase_name, $description, $start_date, $end_date, $duration, $status);
            $msg = "Phase added successfully";
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database Error: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    // -------------------- FETCH ALL PHASES --------------------
    $sql = "SELECT ph.*, p.project_name 
            FROM icmis_project_phases ph
            LEFT JOIN icmis_projects p ON ph.project_id = p.project_id
            ORDER BY ph.start_date DESC";
    $result = $conn->query($sql);
    
    $phases = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $phases[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'phases' => $phases
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
