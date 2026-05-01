<?php
/**
 * ========================= API: Projects =========================
 * Purpose: Handle CRUD operations for projects.
 * Table: projects
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

    // -------------------- DELETE PROJECT --------------------
    if ($method === 'POST' && isset($_POST['delete_id'])) {
        $project_id = intval($_POST['delete_id']);
        
        // Fetch project details for descriptive response
        $stmtGet = $db->prepare("SELECT project_name, project_code FROM projects WHERE project_id = ?");
        $stmtGet->execute([$project_id]);
        $project = $stmtGet->fetch();

        if (!$project) {
            echo json_encode(['success' => false, 'message' => 'Project not found']);
            exit;
        }

        // Delete related records in this context (Project Service)
        $db->prepare("DELETE FROM tasks WHERE project_id = ?")->execute([$project_id]);
        $db->prepare("DELETE FROM project_phases WHERE project_id = ?")->execute([$project_id]);

        // Delete the project
        $stmt = $db->prepare("DELETE FROM projects WHERE project_id = ?");
        $stmt->execute([$project_id]);

        $label = trim(($project['project_name'] ?? '') . ($project['project_code'] ? " ({$project['project_code']})" : ''));
        
        echo json_encode([
            'success' => true,
            'message' => "Deleted project: $label",
            'project_id' => $project_id,
            'project_label' => $label
        ]);
        exit;
    }

    // -------------------- GET NEXT PROJECT CODE --------------------
    if ($method === 'GET' && isset($_GET['get_next_id'])) {
        $year = date('Y');
        $stmt = $db->prepare("SELECT project_code FROM projects WHERE project_code LIKE ? ORDER BY project_id DESC LIMIT 1");
        $stmt->execute(["PRJ-$year-%"]);
        $row = $stmt->fetch();

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
    if ($method === 'GET' && isset($_GET['fetch_id'])) {
        $project_id = intval($_GET['fetch_id']);
        $stmt = $db->prepare("SELECT * FROM projects WHERE project_id = ? LIMIT 1");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();

        echo json_encode([
            'success' => (bool)$project,
            'project' => $project ?: null,
            'message' => $project ? 'Project fetched successfully' : 'Project not found'
        ]);
        exit;
    }

    // -------------------- ADD / EDIT PROJECT --------------------
    if ($method === 'POST') {
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

        if ($project_id > 0) {
            // UPDATE
            $sql = "UPDATE projects SET 
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
            $stmt = $db->prepare($sql);
            $stmt->execute([$project_code, $project_name, $project_manager, $description, $location, $start_date, $end_date, $status, $total_budget, $project_id]);
            
            echo json_encode(['success' => true, 'message' => 'Project updated successfully']);
        } else {
            // INSERT
            $sql = "INSERT INTO projects
                        (project_code, project_name, project_manager_id, description, location, start_date, end_date, status, total_budget)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$project_code, $project_name, $project_manager, $description, $location, $start_date, $end_date, $status, $total_budget]);
            $new_id = $db->lastInsertId();

            echo json_encode(['success' => true, 'message' => 'Project added successfully', 'project_id' => $new_id]);
        }
        exit;
    }

    // -------------------- FETCH ALL PROJECTS --------------------
    $stmt = $db->query("SELECT * FROM projects ORDER BY project_id DESC");
    $projects = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'projects' => $projects
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
