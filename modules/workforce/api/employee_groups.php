<?php
// File: modules/workforce/api/employee_groups.php
header('Content-Type: application/json');
include __DIR__ . '/../project_context.php';

require_once __DIR__ . '/../../../core/Logger.php';

$conn = getWorkforceConnection();

// Handle JSON Input and merge into a unified payload
if (empty($_POST)) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (is_array($input)) $_POST = $input;
}

// Merge $_REQUEST and $_POST so callers can send action/id via GET, form POST, or JSON body
$payload = $_REQUEST;
foreach ($_POST as $k => $v) $payload[$k] = $v;

$action = $payload['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            listGroups($conn);
            break;
        case 'get':
            getGroup($conn, $payload['id'] ?? $payload['group_id'] ?? $payload['group_code'] ?? 0);
            break;
        case 'create':
        case 'update':
            saveGroup($conn, $action);
            break;
        case 'delete':
            deleteGroup($conn, $payload['id'] ?? $payload['group_id'] ?? 0);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();

function listGroups($conn) {
    // Simplified query: No joins to projects or phases
    $sql = "SELECT g.*, 
            CONCAT(l.first_name, ' ', l.last_name) as leader_name,
            (SELECT COUNT(*) FROM workforce_group_memberships gm WHERE gm.group_id = g.group_id) as member_count
            FROM workforce_employee_groups g
            LEFT JOIN workforce_employees l ON g.group_leader_id = l.employee_id
            ORDER BY g.group_name ASC";
    
    $result = $conn->query($sql);
    $groups = [];
    while($row = $result->fetch_assoc()) {
        $groups[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $groups]);
}

function getGroup($conn, $idOrCode) {
    $group = null;
    $resolvedId = 0;

    if (!$idOrCode) throw new Exception("Group identifier required");

    if (is_numeric($idOrCode) && intval($idOrCode) > 0) {
        $resolvedId = intval($idOrCode);
        $stmt = $conn->prepare("SELECT * FROM workforce_employee_groups WHERE group_id = ?");
        $stmt->bind_param("i", $resolvedId);
        $stmt->execute();
        $group = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } else {
        $code = trim($idOrCode);
        $stmt = $conn->prepare("SELECT * FROM workforce_employee_groups WHERE group_code = ? LIMIT 1");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $group = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($group) $resolvedId = intval($group['group_id']);
    }

    if (!$group) {
        throw new Exception("Group not found");
    }

    // Fetch member ids using resolved numeric id
    $members = [];
    if ($resolvedId) {
        $stmt = $conn->prepare("SELECT employee_id FROM workforce_group_memberships WHERE group_id = ?");
        $stmt->bind_param("i", $resolvedId);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()) {
            $members[] = $row['employee_id'];
        }
        $stmt->close();
    }

    $group['members'] = $members;
    echo json_encode(['success' => true, 'data' => $group]);
}

function saveGroup($conn, $action) {
    $name = $_POST['group_name'] ?? '';
    $leader = !empty($_POST['leader_id']) ? $_POST['leader_id'] : null;
    $desc = $_POST['description'] ?? '';
    $members = $_POST['members'] ?? [];

    if (empty($name)) throw new Exception("Group Name is required");

    $conn->begin_transaction();

    try {
        if ($action === 'create') {
            // INSERT without project_id, phase_id
            $stmt = $conn->prepare("INSERT INTO workforce_employee_groups (group_name, group_leader_id, description) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $name, $leader, $desc);
            $stmt->execute();
            $group_id = $conn->insert_id;

            // Generate Code with zero-padded numeric suffix (e.g. GRP-2026-001)
            $year = date('Y');
            $custom_code = sprintf('GRP-%s-%03d', $year, $group_id);
            $conn->query("UPDATE workforce_employee_groups SET group_code = '{$conn->real_escape_string($custom_code)}' WHERE group_id = {$group_id}");

        } else {
            $group_id = $_POST['group_id'] ?? 0;
            if (!$group_id) throw new Exception("Group ID required");
            
            // UPDATE without project_id, phase_id
            $stmt = $conn->prepare("UPDATE workforce_employee_groups SET group_name=?, group_leader_id=?, description=? WHERE group_id=?");
            $stmt->bind_param("sisi", $name, $leader, $desc, $group_id);
            $stmt->execute();

            // Clear members to re-insert
            $conn->query("DELETE FROM workforce_group_memberships WHERE group_id = $group_id");

            // Ensure a group_code exists for older records: generate if missing
            $res = $conn->query("SELECT group_code FROM workforce_employee_groups WHERE group_id = $group_id LIMIT 1");
            if ($res) {
                $row = $res->fetch_assoc();
                if (empty($row['group_code'])) {
                    $year = date('Y');
                    $custom_code = sprintf('GRP-%s-%03d', $year, $group_id);
                    $conn->query("UPDATE workforce_employee_groups SET group_code = '{$conn->real_escape_string($custom_code)}' WHERE group_id = {$group_id}");
                }
            }
        }

        if (!empty($members)) {
            $memStmt = $conn->prepare("
                INSERT INTO workforce_group_memberships (group_id, employee_id, role_in_group, joined_date) 
                SELECT ?, e.employee_id, jt.title_name, CURDATE()
                FROM workforce_employees e
                LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
                WHERE e.employee_id = ?
            ");
            foreach ($members as $emp_id) {
                $memStmt->bind_param("ii", $group_id, $emp_id);
                $memStmt->execute();
            }
        }

        $conn->commit();
        Logger::init($conn);
        if ($action === 'create') {
            Logger::create('Workforce', "Employee Group Created: {$name} (ID: {$group_id})", intval($group_id));
        } else {
            Logger::update('Workforce', "Employee Group Updated: {$name} (ID: {$group_id})", intval($group_id));
        }
        echo json_encode(['success' => true, 'message' => 'Group saved successfully']);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function deleteGroup($conn, $id) {
    if (!$id) throw new Exception("ID required");
    
    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM workforce_group_memberships WHERE group_id = $id");
        $conn->query("DELETE FROM workforce_employee_groups WHERE group_id = $id");
        $conn->commit();
        Logger::init($conn);
        Logger::delete('Workforce', "Employee Group Deleted: ID {$id}", intval($id));
        echo json_encode(['success' => true, 'message' => 'Group deleted successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}
?>