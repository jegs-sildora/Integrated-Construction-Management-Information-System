<?php
/**
 * Attendance API v1 - Workforce Service
 */
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../../Database.php';

$db = Database::getConnection();

// Handle JSON Input
$input = json_decode(file_get_contents('php://input'), true);
if (is_array($input)) {
    $_POST = array_merge($_POST, $input);
    $_REQUEST = array_merge($_REQUEST, $input);
}

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            listAttendance($db);
            break;
        case 'get':
            getAttendance($db);
            break;
        case 'save':
            saveAttendance($db);
            break;
        case 'save_bulk':
            saveBulkAttendance($db);
            break;
        case 'delete':
            deleteAttendance($db, $_REQUEST['id'] ?? 0);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// --- FUNCTIONS ---

function listAttendance($db) {
    $project_id = isset($_REQUEST['project_id']) ? intval($_REQUEST['project_id']) : null;
    $date = $_REQUEST['date'] ?? date('Y-m-d');
    $employee_id = isset($_REQUEST['employee_id']) ? intval($_REQUEST['employee_id']) : null;
    
    $params = [];
    $params[] = $date;

    // Removed join with projects as it's a different bounded context
    $sql = "SELECT e.employee_id, e.employee_code, e.first_name, e.last_name, 
                   jt.title_name as job_title,
                   att.attendance_id, att.time_in, att.time_out, att.status, att.remarks,
                   att.project_id
            FROM employees e
            LEFT JOIN job_titles jt ON e.job_title_id = jt.job_title_id
            LEFT JOIN attendance att ON e.employee_id = att.employee_id AND att.attendance_date = ?";
    
    if ($project_id && $project_id > 0) {
        $sql .= " AND (att.project_id = ? OR att.project_id IS NULL)";
        $params[] = $project_id;
    }
    
    $whereClauses = ["e.status = 'Active'"];

    if ($employee_id) {
        $whereClauses[] = "e.employee_id = ?";
        $params[] = $employee_id;
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(" AND ", $whereClauses);
    }
    
    $sql .= " ORDER BY e.last_name ASC, e.first_name ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $attendance = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $attendance]);
}

function getAttendance($db) {
    $employee_id = $_REQUEST['employee_id'] ?? 0;
    $date = $_REQUEST['date'] ?? date('Y-m-d');
    
    if (!$employee_id) throw new Exception("Employee ID required");
    
    $stmt = $db->prepare("SELECT * FROM attendance WHERE employee_id = ? AND attendance_date = ?");
    $stmt->execute([$employee_id, $date]);
    $row = $stmt->fetch();
    
    echo json_encode(['success' => true, 'data' => $row ?: null]);
}

function saveAttendance($db) {
    $data = $_POST;
    if (empty($data['employee_id']) || empty($data['attendance_date'])) throw new Exception('Employee and date required');
    
    $project_id = !empty($data['project_id']) ? intval($data['project_id']) : null;
    if ($project_id === 0) $project_id = null;

    $time_in = !empty($data['time_in']) ? $data['time_in'] : null;
    $time_out = !empty($data['time_out']) ? $data['time_out'] : null;
    $status = $data['status'] ?? 'Present';
    $remarks = $data['remarks'] ?? '';
    
    // Check existing
    $stmt = $db->prepare("SELECT attendance_id FROM attendance WHERE employee_id = ? AND attendance_date = ?");
    $stmt->execute([$data['employee_id'], $data['attendance_date']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $sql = "UPDATE attendance SET project_id = ?, time_in = ?, time_out = ?, status = ?, remarks = ? WHERE attendance_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$project_id, $time_in, $time_out, $status, $remarks, $existing['attendance_id']]);
    } else {
        $sql = "INSERT INTO attendance (employee_id, project_id, attendance_date, time_in, time_out, status, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$data['employee_id'], $project_id, $data['attendance_date'], $time_in, $time_out, $status, $remarks]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Saved']);
}

function saveBulkAttendance($db) {
    $data = $_POST;
    $records = $data['records'] ?? [];
    $global_project_id = isset($data['project_id']) ? intval($data['project_id']) : null;
    if ($global_project_id === 0) $global_project_id = null;
    $date = $data['date'] ?? date('Y-m-d');
    
    if (empty($records)) throw new Exception('No records');
    
    $db->beginTransaction();
    try {
        $saved = 0;
        foreach ($records as $record) {
            $employee_id = intval($record['employee_id']);
            $record_project_id = isset($record['project_id']) ? intval($record['project_id']) : $global_project_id;
            if ($record_project_id === 0) $record_project_id = null;
            $record_date = !empty($record['date']) ? $record['date'] : $date;
            $time_in = !empty($record['time_in']) ? $record['time_in'] : null;
            $time_out = !empty($record['time_out']) ? $record['time_out'] : null;
            $status = $record['status'] ?? 'Present';
            $remarks = $record['remarks'] ?? '';
            
            if (!$employee_id || !$status) continue;
            
            $stmt = $db->prepare("SELECT attendance_id FROM attendance WHERE employee_id = ? AND attendance_date = ?");
            $stmt->execute([$employee_id, $record_date]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $sql = "UPDATE attendance SET project_id = ?, time_in = ?, time_out = ?, status = ?, remarks = ? WHERE attendance_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$record_project_id, $time_in, $time_out, $status, $remarks, $existing['attendance_id']]);
            } else {
                $sql = "INSERT INTO attendance (employee_id, project_id, attendance_date, time_in, time_out, status, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([$employee_id, $record_project_id, $record_date, $time_in, $time_out, $status, $remarks]);
            }
            $saved++;
        }
        $db->commit();
        echo json_encode(['success' => true, 'message' => "$saved records saved"]);
    } catch (Exception $e) {
        $db->rollBack();
        throw new Exception($e->getMessage());
    }
}

function deleteAttendance($db, $id) {
    if (!$id) throw new Exception('ID required');
    $stmt = $db->prepare("DELETE FROM attendance WHERE attendance_id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Deleted']);
}
