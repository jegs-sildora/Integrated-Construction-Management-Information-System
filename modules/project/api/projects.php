<?php
/**
 * ========================= API: Projects =========================
 * Purpose: Handle CRUD operations for projects.
 * Table: icmis_projects
 * ============================================================================ 
 */

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/Logger.php';
header('Content-Type: application/json');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8mb4");

try {
    // -------------------- DELETE PROJECT --------------------
    if (isset($_POST['delete_id'])) {
        $project_id = intval($_POST['delete_id']);
        // Fetch project details for descriptive logging before deletion
        $projName = '';
        $projCode = '';
        $stmtGet = $conn->prepare("SELECT project_name, project_code FROM icmis_projects WHERE project_id = ? LIMIT 1");
        if ($stmtGet) {
            $stmtGet->bind_param("i", $project_id);
            $stmtGet->execute();
            $resGet = $stmtGet->get_result();
            if ($resGet && $rowGet = $resGet->fetch_assoc()) {
                $projName = $rowGet['project_name'] ?? '';
                $projCode = $rowGet['project_code'] ?? '';
            }
            $stmtGet->close();
        }

        // Delete related records first
        // Tasks and phases (phases have ON DELETE CASCADE for tasks, but ensure both cleared)
        $conn->query("DELETE FROM icmis_tasks WHERE project_id = $project_id");
        $conn->query("DELETE FROM icmis_project_phases WHERE project_id = $project_id");

        // Budget-related data that reference projects without ON DELETE CASCADE
        $conn->query("DELETE FROM budget_expenses WHERE project_id = $project_id");
        $conn->query("DELETE FROM budget_generated_reports WHERE project_id = $project_id");
        // Deleting proposals will cascade to budget_line_items (line items FK has ON DELETE CASCADE)
        $conn->query("DELETE FROM budget_proposals WHERE project_id = $project_id");

        // Procurement orders referencing the project (their items typically cascade)
        $conn->query("DELETE FROM procurement_purchase_orders WHERE project_id = $project_id");

        // Optional: cleanup budget-generated reports (table exists in DB dump as `budget_generated_reports`)
        $conn->query("DELETE FROM budget_generated_reports WHERE project_id = $project_id");
        
        $stmt = $conn->prepare("DELETE FROM icmis_projects WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();

        // Log project deletion to audit trail
        if ($stmt->affected_rows > 0) {
            Logger::init($conn);
            // Prefer descriptive message including project name and code when available
            if ($projName || $projCode) {
                $label = trim(($projName ? $projName : '') . ($projCode ? " ($projCode)" : ''));
                Logger::delete('Project', "Deleted project: $label", $project_id);
            } else {
                Logger::delete('Project', "Deleted project ID: $project_id", $project_id);
            }
        }

        // Prepare descriptive message for client
        $deleted = $stmt->affected_rows > 0;
        if ($deleted) {
            $label = trim(($projName ? $projName : '') . ($projCode ? " ($projCode)" : ''));
            $msg = $label ? "Deleted project: $label" : 'Project deleted successfully';
        } else {
            $msg = 'Project not found';
            $label = null;
        }

        echo json_encode([
            'success' => $deleted,
            'message' => $msg,
            'project_id' => $deleted ? $project_id : null,
            'project_label' => $label
        ]);
        $stmt->close();
        exit;
    }

    // -------------------- GET NEXT PROJECT CODE --------------------
    if (isset($_GET['get_next_id'])) {
        $year = date('Y');
        $result = $conn->query("SELECT project_code FROM icmis_projects WHERE project_code LIKE 'PRJ-$year-%' ORDER BY project_id DESC LIMIT 1");
        $row = $result->fetch_assoc();

        if ($row) {
            $num = intval(substr($row['project_code'], -3)) + 1;
        } else {
            $num = 1;
        }
        $nextCode = "PRJ-$year-" . str_pad($num, 3, "0", STR_PAD_LEFT);

        echo json_encode(['success' => true, 'project_code' => $nextCode]);
        exit;
    }

    // -------------------- FETCH SINGLE PROJECT --------------------
    if (isset($_GET['fetch_id'])) {
        $project_id = intval($_GET['fetch_id']);
        $stmt = $conn->prepare("
            SELECT p.*, CONCAT(e.first_name, ' ', e.last_name) AS manager_name
            FROM icmis_projects p
            LEFT JOIN workforce_employees e ON p.project_manager_id = e.employee_id
            WHERE p.project_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $project = $result->fetch_assoc();
        $stmt->close();

        echo json_encode([
            'success' => (bool)$project,
            'project' => $project ?: null,
            'message' => $project ? 'Project fetched successfully' : 'Project not found'
        ]);
        exit;
    }

    // -------------------- ADD / EDIT PROJECT --------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $project_code = trim($_POST['project_code'] ?? '');
        $project_name = trim($_POST['project_name'] ?? '');
        $project_manager = !empty($_POST['project_manager_id']) ? intval($_POST['project_manager_id']) : null;
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $status = $_POST['status'] ?? 'Planning';
        $total_budget = floatval($_POST['total_budget'] ?? 0);

        // Check if project exists
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM icmis_projects WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc()['cnt'] > 0;
        $stmt->close();

        if ($exists) {
            // UPDATE
            $sql = "UPDATE icmis_projects SET 
                        project_code = ?,
                        project_name = ?,
                        project_manager_id = ?,
                        description = ?,
                        location = ?,
                        start_date = ?,
                        end_date = ?,
                        status = ?,
                        total_budget = ?
                    WHERE project_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssisssssdi", $project_code, $project_name, $project_manager, $description, $location, $start_date, $end_date, $status, $total_budget, $project_id);
            $stmt->execute();
            $stmt->close();

            // Log project update to audit trail
            Logger::init($conn);
            Logger::update('Project', "Updated project: $project_name ($project_code)", $project_id);

            echo json_encode(['success' => true, 'message' => 'Project updated successfully']);
        } else {
            // INSERT
            $sql = "INSERT INTO icmis_projects
                        (project_code, project_name, project_manager_id, description, location, start_date, end_date, status, total_budget)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssisssssd", $project_code, $project_name, $project_manager, $description, $location, $start_date, $end_date, $status, $total_budget);
            $stmt->execute();
            $new_id = $conn->insert_id;
            $stmt->close();

            // Log project creation to audit trail
            Logger::init($conn);
            Logger::create('Project', "Created new project: $project_name ($project_code)", $new_id);

            echo json_encode(['success' => true, 'message' => 'Project added successfully', 'project_id' => $new_id]);
        }
        exit;
    }

    // -------------------- FETCH ALL PROJECTS --------------------
        $sql = "SELECT p.*, CONCAT(e.first_name, ' ', e.last_name) AS manager_name
            FROM icmis_projects p
            LEFT JOIN workforce_employees e ON p.project_manager_id = e.employee_id
            ORDER BY p.project_id DESC";
    $result = $conn->query($sql);
    
    $projects = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'projects' => $projects
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
