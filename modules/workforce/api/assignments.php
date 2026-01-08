<?php
/**
 * Assignments API - Workforce Module
 * Handles CRUD operations for workforce project assignments
 * Implements "Unified Assignments" as the single source of location truth.
 */
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include __DIR__ . '/../project_context.php';

$conn = getWorkforceConnection();

// 1. Handle JSON Input (Crucial for Fetch API)
// This allows the script to read data sent as application/json
if (empty($_POST)) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (is_array($input)) {
        $_POST = $input;
        $_REQUEST = array_merge($_REQUEST, $input);
    }
}

// 2. Action Detection
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            listAssignments($conn);
            break;
        case 'get':
            getAssignment($conn, $_REQUEST['id'] ?? 0);
            break;
        case 'create':
            createAssignment($conn);
            break;
        case 'update':
            updateAssignment($conn);
            break;
        case 'delete':
            deleteAssignment($conn, $_REQUEST['id'] ?? 0);
            break;
        case 'get_phases':
            getPhases($conn, $_REQUEST['project_id'] ?? 0);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();

// --- FUNCTIONS ---

function listAssignments($conn) {
    $project_id = isset($_REQUEST['project_id']) ? intval($_REQUEST['project_id']) : null;
    $status = $_REQUEST['status'] ?? null;
    $search = $_REQUEST['search'] ?? '';
    
    $sql = "SELECT a.*, 
                   e.employee_code, e.first_name, e.last_name,
                   p.project_name, p.project_code,
                   ph.phase_name
            FROM workforce_assignments a
            JOIN workforce_employees e ON a.employee_id = e.employee_id
            JOIN icmis_projects p ON a.project_id = p.project_id
            LEFT JOIN icmis_project_phases ph ON a.phase_id = ph.phase_id
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Filter by Project (Context Awareness)
    if ($project_id && $project_id > 0) {
        $sql .= " AND a.project_id = ?";
        $params[] = $project_id;
        $types .= 'i';
    }
    
    if ($status && $status !== 'all') {
        $sql .= " AND a.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if ($search) {
        $sql .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ? OR a.role LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssss';
    }
    
    $sql .= " ORDER BY a.status ASC, a.start_date DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    $stmt->close();
    
    echo json_encode(['success' => true, 'data' => $assignments]);
}

function getAssignment($conn, $id) {
    if (!$id) throw new Exception("Assignment ID required");
    
    $sql = "SELECT a.*, 
                   e.employee_code, e.first_name, e.last_name,
                   p.project_name,
                   ph.phase_name
            FROM workforce_assignments a
            JOIN workforce_employees e ON a.employee_id = e.employee_id
            JOIN icmis_projects p ON a.project_id = p.project_id
            LEFT JOIN icmis_project_phases ph ON a.phase_id = ph.phase_id
            WHERE a.assignment_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row, 'assignment' => $row]); // Return both formats for compatibility
    } else {
        throw new Exception("Assignment not found");
    }
    $stmt->close();
}

function createAssignment($conn) {
    $data = $_POST;
    
    if (empty($data['employee_id']) || empty($data['project_id'])) {
        throw new Exception("Employee and Project are required fields");
    }
    
    // 1. Prevent Duplicate Active Assignments
    // A person cannot be 'Active' on the same project twice at the same time.
    $stmt = $conn->prepare("SELECT assignment_id FROM workforce_assignments 
                           WHERE employee_id = ? AND project_id = ? AND status = 'Active'");
    $stmt->bind_param("ii", $data['employee_id'], $data['project_id']);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        // We throw a specific message so the frontend (bulk loader) knows
        throw new Exception("Employee is already active on this project.");
    }
    $stmt->close();

    // 2. Auto-Populate Role from Job Title if empty
    $role = $data['role'] ?? '';
    if (empty($role)) {
        $roleStmt = $conn->prepare("SELECT jt.title_name 
                                    FROM workforce_employees e 
                                    LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id 
                                    WHERE e.employee_id = ?");
        $roleStmt->bind_param("i", $data['employee_id']);
        $roleStmt->execute();
        $res = $roleStmt->get_result()->fetch_assoc();
        if ($res && $res['title_name']) {
            $role = $res['title_name'];
        }
        $roleStmt->close();
    }
    
    // 3. Insert Assignment
    $sql = "INSERT INTO workforce_assignments 
                (employee_id, project_id, phase_id, role, task_description, start_date, end_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    $phase_id = !empty($data['phase_id']) ? intval($data['phase_id']) : null;
    $status = $data['status'] ?? 'Active';
    $task_description = $data['task_description'] ?? null;
    $start_date = !empty($data['start_date']) ? $data['start_date'] : date('Y-m-d');
    $end_date = !empty($data['end_date']) ? $data['end_date'] : null;
    
    $stmt->bind_param("iiisssss",
        $data['employee_id'],
        $data['project_id'],
        $phase_id,
        $role,
        $task_description,
        $start_date,
        $end_date,
        $status
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Assignment created successfully',
            'id' => $conn->insert_id
        ]);
    } else {
        throw new Exception("Database Error: " . $stmt->error);
    }
    $stmt->close();
}

function updateAssignment($conn) {
    $data = $_POST;
    
    if (empty($data['assignment_id'])) {
        throw new Exception("Assignment ID required");
    }
    
    $sql = "UPDATE workforce_assignments SET 
                employee_id = ?,
                project_id = ?,
                phase_id = ?,
                role = ?,
                task_description = ?,
                start_date = ?,
                end_date = ?,
                status = ?
            WHERE assignment_id = ?";
    
    $stmt = $conn->prepare($sql);
    
    $phase_id = !empty($data['phase_id']) ? intval($data['phase_id']) : null;
    $task_description = $data['task_description'] ?? null;
    $end_date = !empty($data['end_date']) ? $data['end_date'] : null;
    $role = $data['role'] ?? '';
    
    $stmt->bind_param("iiisssssi",
        $data['employee_id'],
        $data['project_id'],
        $phase_id,
        $role,
        $task_description,
        $data['start_date'],
        $end_date,
        $data['status'],
        $data['assignment_id']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Assignment updated successfully']);
    } else {
        throw new Exception("Database Error: " . $stmt->error);
    }
    $stmt->close();
}

function deleteAssignment($conn, $id) {
    if (!$id) throw new Exception("Assignment ID required");
    
    $stmt = $conn->prepare("DELETE FROM workforce_assignments WHERE assignment_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Assignment deleted successfully']);
    } else {
        throw new Exception("Database Error: " . $stmt->error);
    }
    $stmt->close();
}

function getPhases($conn, $project_id) {
    if (!$project_id) {
        echo json_encode(['success' => true, 'data' => []]);
        return;
    }
    
    $stmt = $conn->prepare("SELECT phase_id, phase_name FROM icmis_project_phases WHERE project_id = ? ORDER BY start_date ASC, phase_name ASC");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $phases = [];
    while ($row = $result->fetch_assoc()) {
        $phases[] = $row;
    }
    $stmt->close();
    
    echo json_encode(['success' => true, 'data' => $phases]);
}
?>