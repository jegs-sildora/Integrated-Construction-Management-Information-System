<?php
error_reporting(0);
ini_set('display_errors', 0);
/**
 * Employee Groups API v1 - Workforce Service
 */
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../Logger.php';

$db = \Database::getConnection();

// Helper
// Handle JSON Input
if (empty($_POST)) {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    if (is_array($input)) $_POST = $input;
}

$payload = $_REQUEST;
foreach ($_POST as $k => $v) $payload[$k] = $v;

$action = $payload['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            listGroups($db);
            break;
        case 'get':
            getGroup($db, $payload['id'] ?? $payload['group_id'] ?? $payload['group_code'] ?? 0);
            break;
        case 'create':
        case 'update':
            saveGroup($db, $action);
            break;
        case 'delete':
            deleteGroup($db, $payload['id'] ?? $payload['group_id'] ?? 0);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function listGroups($db) {
    $sql = "SELECT g.*, 
            CONCAT(l.first_name, ' ', l.last_name) as leader_name,
            (SELECT COUNT(*) FROM group_memberships gm WHERE gm.group_id = g.group_id) as member_count
            FROM employee_groups g
            LEFT JOIN employees l ON g.group_leader_id = l.employee_id
            ORDER BY g.group_name ASC";
    
    $stmt = $db->query($sql);
    $groups = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $groups]);
}

function getGroup($db, $idOrCode) {
    $group = null;
    $resolvedId = 0;

    if (!$idOrCode) throw new Exception("Group identifier required");

    if (is_numeric($idOrCode) && intval($idOrCode) > 0) {
        $resolvedId = intval($idOrCode);
        $stmt = $db->prepare("SELECT * FROM employee_groups WHERE group_id = ?");
        $stmt->execute([$resolvedId]);
        $group = $stmt->fetch();
    } else {
        $code = trim($idOrCode);
        $stmt = $db->prepare("SELECT * FROM employee_groups WHERE group_code = ? LIMIT 1");
        $stmt->execute([$code]);
        $group = $stmt->fetch();
        if ($group) $resolvedId = intval($group['group_id']);
    }

    if (!$group) {
        throw new Exception("Group not found");
    }

    $members = [];
    if ($resolvedId) {
        $stmt = $db->prepare("SELECT employee_id FROM group_memberships WHERE group_id = ?");
        $stmt->execute([$resolvedId]);
        while($row = $stmt->fetch()) {
            $members[] = $row['employee_id'];
        }
    }

    $group['members'] = $members;
    echo json_encode(['success' => true, 'data' => $group]);
}

function saveGroup($db, $action) {
    $name = $_POST['group_name'] ?? '';
    $leader = !empty($_POST['leader_id']) ? intval($_POST['leader_id']) : null;
    $desc = $_POST['description'] ?? '';
    $members = $_POST['members'] ?? [];

    if (empty($name)) throw new Exception("Group Name is required");

    $db->beginTransaction();

    try {
        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO employee_groups (group_name, group_leader_id, description) VALUES (?, ?, ?) RETURNING group_id");
            $stmt->execute([$name, $leader, $desc]);
            $group_id = intval($stmt->fetchColumn());

            $year = date('Y');
            $custom_code = sprintf('GRP-%s-%03d', $year, $group_id);
            $db->prepare("UPDATE employee_groups SET group_code = ? WHERE group_id = ?")->execute([$custom_code, $group_id]);
            
            Logger::create('Workforce', "Created employee group: $name ($custom_code)", $group_id);

        } else {
            $group_id = intval($_POST['group_id'] ?? 0);
            if (!$group_id) throw new Exception("Group ID required");
            
            $stmt = $db->prepare("UPDATE employee_groups SET group_name=?, group_leader_id=?, description=? WHERE group_id=?");
            $stmt->execute([$name, $leader, $desc, $group_id]);

            $db->prepare("DELETE FROM group_memberships WHERE group_id = ?")->execute([$group_id]);

            $stmt = $db->prepare("SELECT group_code FROM employee_groups WHERE group_id = ? LIMIT 1");
            $stmt->execute([$group_id]);
            $row = $stmt->fetch();
            if ($row && empty($row['group_code'])) {
                $year = date('Y');
                $custom_code = sprintf('GRP-%s-%03d', $year, $group_id);
                $db->prepare("UPDATE employee_groups SET group_code = ? WHERE group_id = ?")->execute([$custom_code, $group_id]);
            }
            
            Logger::update('Workforce', "Updated employee group: $name", $group_id);
        }

        if (!empty($members)) {
            $memStmt = $db->prepare("
                INSERT INTO group_memberships (group_id, employee_id, role_in_group, joined_date) 
                SELECT ?, e.employee_id, jt.title_name, CURRENT_DATE
                FROM employees e
                LEFT JOIN job_titles jt ON e.job_title_id = jt.job_title_id
                WHERE e.employee_id = ?
            ");
            foreach ($members as $emp_id) {
                $memStmt->execute([$group_id, $emp_id]);
            }
        }

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Group saved successfully']);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

function deleteGroup($db, $id) {
    $id = intval($id);
    if (!$id) throw new Exception("ID required");
    
    // Get name for logging
    $stmtName = $db->prepare("SELECT group_name FROM employee_groups WHERE group_id = ?");
    $stmtName->execute([$id]);
    $name = $stmtName->fetchColumn();

    $db->beginTransaction();
    try {
        $db->prepare("DELETE FROM group_memberships WHERE group_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM employee_groups WHERE group_id = ?")->execute([$id]);
        
        if ($name) {
            Logger::delete('Workforce', "Deleted employee group: $name", $id);
        }
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Group deleted successfully']);
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

