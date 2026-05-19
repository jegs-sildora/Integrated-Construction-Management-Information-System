<?php
/**
 * Assignments API v1 - Workforce Service
 */
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../Logger.php';

$db = \Database::getConnection();

// Helper
function jsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

// Input Handling
if (empty($_POST)) {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    if (is_array($input)) {
        $_POST = $input;
        $_REQUEST = array_merge($_REQUEST, $input);
    }
}
$action = $_REQUEST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            listAssignments($db);
            break;
        case 'create':
            createAssignment($db);
            break;
        case 'update':
            updateAssignment($db);
            break;
        case 'delete':
            deleteAssignment($db);
            break;
        case 'get_phases':
            getPhases($db);
            break;
        case 'get_assignment':
            getAssignment($db);
            break;
        default:
            if (empty($action)) {
                listAssignments($db);
            } else {
                jsonResponse(false, 'Invalid action: ' . $action);
            }
    }
} catch (Exception $e) {
    jsonResponse(false, $e->getMessage());
}

// --- FUNCTIONS ---

function listAssignments($db) {
    $project_id = isset($_REQUEST['project_id']) ? intval($_REQUEST['project_id']) : 0;
    $page = isset($_REQUEST['page']) ? max(1, intval($_REQUEST['page'])) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $where = "WHERE 1=1";
    $params = [];

    if ($project_id > 0) {
        $where .= " AND wa.project_id = ?";
        $params[] = $project_id;
    }

    // 1. Get Count
    $countSql = "SELECT COUNT(*) as total FROM assignments wa $where";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $totalRows = $stmt->fetch()['total'];

    // 2. Get Data
    $sql = "SELECT wa.*, 
            e.first_name, e.last_name, e.employee_code
            FROM assignments wa
            LEFT JOIN employees e ON wa.employee_id = e.employee_id
            $where
            ORDER BY wa.status ASC, wa.start_date DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    jsonResponse(true, 'Data fetched', [
        'assignments' => $data,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $limit > 0 ? ceil($totalRows / $limit) : 1,
            'total_records' => (int)$totalRows
        ]
    ]);
}

function createAssignment($db) {
    $mode = $_POST['mode'] ?? 'individual';
    $project_id = intval($_POST['project_id']);
    $phase_id = !empty($_POST['phase_id']) ? intval($_POST['phase_id']) : null;
    $start_date = $_POST['start_date'];
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $status = 'Active';

    if ($mode === 'group') {
        $group_id = intval($_POST['group_id']);
        
        $mStmt = $db->prepare("SELECT employee_id, role_in_group FROM group_memberships WHERE group_id = ?");
        $mStmt->execute([$group_id]);
        $members = $mStmt->fetchAll();

        if (empty($members)) jsonResponse(false, "Selected group has no members.");

        $count = 0;
        $checkStmt = $db->prepare("SELECT assignment_id FROM assignments WHERE employee_id = ? AND project_id = ? AND status = 'Active'");
        $insStmt = $db->prepare("INSERT INTO assignments (employee_id, project_id, phase_id, role, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($members as $mem) {
            $empId = $mem['employee_id'];
            $role = $mem['role_in_group'] ?: 'Member';

            $checkStmt->execute([$empId, $project_id]);
            if ($checkStmt->rowCount() == 0) {
                if ($insStmt->execute([$empId, $project_id, $phase_id, $role, $start_date, $end_date, $status])) {
                    $count++;
                }
            }
        }
        
        Logger::create('Workforce', "Assigned Group ID #$group_id to Project ID #$project_id ($count members assigned)", null);
        jsonResponse(true, "Group processed. $count members assigned.");
    } else {
        $employee_id = intval($_POST['employee_id']);
        $role = $_POST['role'];

        $checkStmt = $db->prepare("SELECT assignment_id FROM assignments WHERE employee_id = ? AND project_id = ? AND status = 'Active'");
        $checkStmt->execute([$employee_id, $project_id]);
        if ($checkStmt->rowCount() > 0) jsonResponse(false, "Employee already active here.");

        if (empty($role)) {
            $rStmt = $db->prepare("SELECT jt.title_name FROM employees e JOIN job_titles jt ON e.job_title_id = jt.job_title_id WHERE e.employee_id = ?");
            $rStmt->execute([$employee_id]);
            $res = $rStmt->fetch();
            $role = $res ? $res['title_name'] : 'Staff';
        }

        $stmt = $db->prepare("INSERT INTO assignments (employee_id, project_id, phase_id, role, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING assignment_id");
        if ($stmt->execute([$employee_id, $project_id, $phase_id, $role, $start_date, $end_date, $status])) {
            $new_id = intval($stmt->fetchColumn());
            Logger::create('Workforce', "Created assignment for Employee #$employee_id to Project #$project_id", $new_id);
            jsonResponse(true, "Assignment created.");
        } else {
            jsonResponse(false, "Failed to create assignment.");
        }
    }
}

function updateAssignment($db) {
    $id = intval($_POST['assignment_id']);
    $role = $_POST['role'];
    $phase_id = !empty($_POST['phase_id']) ? intval($_POST['phase_id']) : null;
    $start_date = $_POST['start_date'];
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $status = $_POST['status'];

    $stmt = $db->prepare("UPDATE assignments SET role=?, phase_id=?, start_date=?, end_date=?, status=? WHERE assignment_id=?");
    if ($stmt->execute([$role, $phase_id, $start_date, $end_date, $status, $id])) {
        Logger::update('Workforce', "Updated assignment ID #$id (Status: $status)", $id);
        jsonResponse(true, "Updated successfully.");
    } else {
        jsonResponse(false, "Update failed.");
    }
}

function deleteAssignment($db) {
    $id = intval($_POST['id']);
    
    $stmtInf = $db->prepare("SELECT employee_id, project_id FROM assignments WHERE assignment_id = ?");
    $stmtInf->execute([$id]);
    $inf = $stmtInf->fetch();

    $stmt = $db->prepare("DELETE FROM assignments WHERE assignment_id = ?");
    if ($stmt->execute([$id])) {
        if ($inf) {
            Logger::delete('Workforce', "Deleted assignment for Employee #{$inf['employee_id']} from Project #{$inf['project_id']}", $id);
        }
        jsonResponse(true, "Deleted successfully.");
    } else {
        jsonResponse(false, "Delete failed.");
    }
}

function getPhases($db) {
    try {
        $pid = intval($_REQUEST['project_id']);
        if ($pid <= 0) throw new Exception("Project ID required");

        // Fetch from Project Service
        $url = 'http://project-service/api/v1/phases.php?project_id=' . $pid;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $res = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($status !== 200) throw new Exception("Could not fetch phases from Project Service");
        
        $data = json_decode($res, true);
        jsonResponse(true, "Loaded", $data['phases'] ?? []);
    } catch (Exception $e) {
        jsonResponse(false, $e->getMessage());
    }
}

function getAssignment($db) {
    $id = intval($_REQUEST['id']);
    $stmt = $db->prepare("SELECT * FROM assignments WHERE assignment_id = ?");
    $stmt->execute([$id]);
    jsonResponse(true, "Loaded", $stmt->fetch());
}

