<?php
/**
 * Assignments API - Workforce Module
 * Location: /api/assignments.php
 */
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// 1. Connection Logic
// We try to include the context. If getWorkforceConnection doesn't exist, we fall back to standard db.
if (file_exists(__DIR__ . '/../project_context.php')) {
    include_once __DIR__ . '/../project_context.php';
}
if (file_exists(__DIR__ . '/../../core/database.php')) {
    include_once __DIR__ . '/../../core/database.php';
}

// Establish connection
if (function_exists('getWorkforceConnection')) {
    $conn = getWorkforceConnection();
} elseif (isset($conn)) {
    // $conn is already set by database.php
} else {
    // Fallback manual connection if all else fails
    include __DIR__ . '/../../config/config.php';
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
}

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB Connection Failed']);
    exit;
}

// Helper
function jsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

// 2. Input Handling
if (empty($_POST)) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (is_array($input)) {
        $_POST = $input;
        $_REQUEST = array_merge($_REQUEST, $input);
    }
}

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            listAssignments($conn);
            break;
        case 'create':
            createAssignment($conn);
            break;
        case 'update':
            updateAssignment($conn);
            break;
        case 'delete':
            deleteAssignment($conn);
            break;
        case 'get_phases':
            getPhases($conn);
            break;
        case 'get_assignment':
            getAssignment($conn);
            break;
        default:
            jsonResponse(false, 'Invalid action');
    }
} catch (Exception $e) {
    jsonResponse(false, $e->getMessage());
}

// --- FUNCTIONS ---

function listAssignments($conn) {
    $project_id = isset($_REQUEST['project_id']) ? intval($_REQUEST['project_id']) : 0;
    $page = isset($_REQUEST['page']) ? max(1, intval($_REQUEST['page'])) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $where = "WHERE 1=1";
    $params = [];
    $types = "";

    // IMPORTANT: Only filter if project_id is strictly greater than 0
    if ($project_id > 0) {
        $where .= " AND wa.project_id = ?";
        $params[] = $project_id;
        $types .= "i";
    }

    // 1. Get Count
    $countSql = "SELECT COUNT(*) as total FROM workforce_assignments wa $where";
    $stmt = $conn->prepare($countSql);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $totalRows = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // 2. Get Data
    // We LEFT JOIN to ensure we get the assignment even if the employee was deleted
    $sql = "SELECT wa.*, 
            e.first_name, e.last_name, e.employee_code, 
            p.project_name, 
            ph.phase_name
            FROM workforce_assignments wa
            LEFT JOIN workforce_employees e ON wa.employee_id = e.employee_id
            LEFT JOIN icmis_projects p ON wa.project_id = p.project_id
            LEFT JOIN icmis_project_phases ph ON wa.phase_id = ph.phase_id
            $where
            ORDER BY wa.status ASC, wa.start_date DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    jsonResponse(true, 'Data fetched', [
        'assignments' => $data,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $limit > 0 ? ceil($totalRows / $limit) : 1,
            'total_records' => $totalRows
        ]
    ]);
}

function createAssignment($conn) {
    $mode = $_POST['mode'] ?? 'individual';
    $project_id = $_POST['project_id'];
    $phase_id = !empty($_POST['phase_id']) ? $_POST['phase_id'] : null;
    $start_date = $_POST['start_date'];
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $status = 'Active';

    if ($mode === 'group') {
        $group_id = $_POST['group_id'];
        
        $mStmt = $conn->prepare("SELECT employee_id, role_in_group FROM workforce_group_memberships WHERE group_id = ?");
        $mStmt->bind_param("i", $group_id);
        $mStmt->execute();
        $members = $mStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $mStmt->close();

        if (empty($members)) jsonResponse(false, "Selected group has no members.");

        $count = 0;
        $checkStmt = $conn->prepare("SELECT assignment_id FROM workforce_assignments WHERE employee_id = ? AND project_id = ? AND status = 'Active'");
        $insStmt = $conn->prepare("INSERT INTO workforce_assignments (employee_id, project_id, phase_id, role, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($members as $mem) {
            $empId = $mem['employee_id'];
            $role = $mem['role_in_group'] ?: 'Member';

            $checkStmt->bind_param("ii", $empId, $project_id);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows == 0) {
                $insStmt->bind_param("iiissss", $empId, $project_id, $phase_id, $role, $start_date, $end_date, $status);
                if ($insStmt->execute()) $count++;
            }
        }
        jsonResponse(true, "Group processed. $count members assigned.");
    } else {
        $employee_id = $_POST['employee_id'];
        $role = $_POST['role'];

        $checkStmt = $conn->prepare("SELECT assignment_id FROM workforce_assignments WHERE employee_id = ? AND project_id = ? AND status = 'Active'");
        $checkStmt->bind_param("ii", $employee_id, $project_id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) jsonResponse(false, "Employee already active here.");

        if (empty($role)) {
            $rStmt = $conn->prepare("SELECT jt.title_name FROM workforce_employees e JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id WHERE e.employee_id = ?");
            $rStmt->bind_param("i", $employee_id);
            $rStmt->execute();
            $res = $rStmt->get_result()->fetch_assoc();
            $role = $res ? $res['title_name'] : 'Staff';
        }

        $stmt = $conn->prepare("INSERT INTO workforce_assignments (employee_id, project_id, phase_id, role, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiissss", $employee_id, $project_id, $phase_id, $role, $start_date, $end_date, $status);
        if ($stmt->execute()) jsonResponse(true, "Assignment created.");
        else jsonResponse(false, "DB Error: " . $stmt->error);
    }
}

function updateAssignment($conn) {
    $id = $_POST['assignment_id'];
    $role = $_POST['role'];
    $phase_id = !empty($_POST['phase_id']) ? $_POST['phase_id'] : null;
    $start_date = $_POST['start_date'];
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE workforce_assignments SET role=?, phase_id=?, start_date=?, end_date=?, status=? WHERE assignment_id=?");
    $stmt->bind_param("sisssi", $role, $phase_id, $start_date, $end_date, $status, $id);
    if ($stmt->execute()) jsonResponse(true, "Updated successfully.");
    else jsonResponse(false, "Update failed.");
}

function deleteAssignment($conn) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM workforce_assignments WHERE assignment_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) jsonResponse(true, "Deleted successfully.");
    else jsonResponse(false, "Delete failed.");
}

function getPhases($conn) {
    $pid = $_REQUEST['project_id'];
    $stmt = $conn->prepare("SELECT phase_id, phase_name FROM icmis_project_phases WHERE project_id = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    jsonResponse(true, "Loaded", $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

function getAssignment($conn) {
    $id = $_REQUEST['id'];
    $stmt = $conn->prepare("SELECT * FROM workforce_assignments WHERE assignment_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    jsonResponse(true, "Loaded", $stmt->get_result()->fetch_assoc());
}
?>