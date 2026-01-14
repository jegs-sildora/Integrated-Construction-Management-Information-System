<?php
/**
 * Attendance API - Workforce Module
 */
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include __DIR__ . '/../project_context.php';

require_once __DIR__ . '/../../core/Logger.php';

$conn = getWorkforceConnection();

// 1. Handle JSON Input
$input = json_decode(file_get_contents('php://input'), true);
if (is_array($input)) {
    $_POST = array_merge($_POST, $input);
    $_REQUEST = array_merge($_REQUEST, $input);
}

// 2. Action Detection
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            listAttendance($conn);
            break;
        case 'get':
            getAttendance($conn);
            break;
        case 'save':
            saveAttendance($conn);
            break;
        case 'save_bulk':
            saveBulkAttendance($conn);
            break;
        case 'delete':
            deleteAttendance($conn, $_REQUEST['id'] ?? 0);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();

// --- FUNCTIONS ---

function listAttendance($conn) {
    $project_id = isset($_REQUEST['project_id']) ? intval($_REQUEST['project_id']) : null;
    $date = $_REQUEST['date'] ?? date('Y-m-d');
    $employee_id = isset($_REQUEST['employee_id']) ? intval($_REQUEST['employee_id']) : null;
    
    $params = [];
    $types = '';

    // FIX: Always fetch ALL active employees. Do not restrict by Assignments.
    $sql = "SELECT e.employee_id, e.employee_code, e.first_name, e.last_name, 
                   jt.title_name as job_title,
                   att.attendance_id, att.time_in, att.time_out, att.status, att.remarks,
                   p.project_name
            FROM workforce_employees e
            LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
            -- Join Attendance for the specific Date and (optional) Project
            LEFT JOIN workforce_attendance att ON e.employee_id = att.employee_id AND att.attendance_date = ?";
    
    $params[] = $date;
    $types .= 's';

    // If project is selected, we only match attendance records for that project,
    // BUT we still keep the employee in the list (with null status) if they have no record.
    if ($project_id && $project_id > 0) {
        $sql .= " AND (att.project_id = ? OR att.project_id IS NULL)";
        $params[] = $project_id;
        $types .= 'i';
    }
    
    $sql .= " LEFT JOIN icmis_projects p ON att.project_id = p.project_id";
    
    // Always filter for Active employees only
    $whereClauses = ["e.status = 'Active'"];

    if ($employee_id) {
        $whereClauses[] = "e.employee_id = ?";
        $params[] = $employee_id;
        $types .= 'i';
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(" AND ", $whereClauses);
    }
    
    $sql .= " ORDER BY e.last_name ASC, e.first_name ASC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $attendance = [];
    while ($row = $result->fetch_assoc()) {
        $attendance[] = $row;
    }
    $stmt->close();
    
    echo json_encode(['success' => true, 'data' => $attendance]);

    // Audit: Log when a user checks attendance for today
    if ($date === date('Y-m-d')) {
        Logger::init($conn);
        $project_label = ($project_id && $project_id > 0) ? " for project #{$project_id}" : " (All Projects)";
        Logger::create('Workforce', "Checked attendance for today" . $project_label, $project_id && $project_id > 0 ? intval($project_id) : null);
    }
}

function getAttendance($conn) {
    $employee_id = $_REQUEST['employee_id'] ?? 0;
    $date = $_REQUEST['date'] ?? date('Y-m-d');
    
    if (!$employee_id) throw new Exception("Employee ID required");
    
    $stmt = $conn->prepare("SELECT * FROM workforce_attendance WHERE employee_id = ? AND attendance_date = ?");
    $stmt->bind_param("is", $employee_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => true, 'data' => null]);
    }
    $stmt->close();
}

function saveAttendance($conn) {
    $data = $_POST;
    if (empty($data['employee_id']) || empty($data['attendance_date'])) throw new Exception('Employee and date required');
    
    $project_id = !empty($data['project_id']) ? intval($data['project_id']) : null;
    if ($project_id === 0) $project_id = null;

    $time_in = !empty($data['time_in']) ? $data['time_in'] : null;
    $time_out = !empty($data['time_out']) ? $data['time_out'] : null;
    $status = $data['status'] ?? 'Present';
    $remarks = $data['remarks'] ?? '';
    
    // Check existing
    $stmt = $conn->prepare("SELECT attendance_id FROM workforce_attendance WHERE employee_id = ? AND attendance_date = ?");
    $stmt->bind_param("is", $data['employee_id'], $data['attendance_date']);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($existing) {
        $sql = "UPDATE workforce_attendance SET project_id = ?, time_in = ?, time_out = ?, status = ?, remarks = ? WHERE attendance_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssi", $project_id, $time_in, $time_out, $status, $remarks, $existing['attendance_id']);
    } else {
        $sql = "INSERT INTO workforce_attendance (employee_id, project_id, attendance_date, time_in, time_out, status, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssss", $data['employee_id'], $project_id, $data['attendance_date'], $time_in, $time_out, $status, $remarks);
    }
    
    if ($stmt->execute()) echo json_encode(['success' => true, 'message' => 'Saved']);
    else throw new Exception('DB Error: ' . $stmt->error);
    $stmt->close();
}

function saveBulkAttendance($conn) {
    $data = $_POST;
    $records = $data['records'] ?? [];
    $global_project_id = isset($data['project_id']) ? intval($data['project_id']) : null;
    if ($global_project_id === 0) $global_project_id = null;
    $date = $data['date'] ?? date('Y-m-d');
    
    if (empty($records)) throw new Exception('No records');
    
    $conn->begin_transaction();
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
            
            $stmt = $conn->prepare("SELECT attendance_id FROM workforce_attendance WHERE employee_id = ? AND attendance_date = ?");
            $stmt->bind_param("is", $employee_id, $record_date);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($existing) {
                $sql = "UPDATE workforce_attendance SET project_id = ?, time_in = ?, time_out = ?, status = ?, remarks = ? WHERE attendance_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issssi", $record_project_id, $time_in, $time_out, $status, $remarks, $existing['attendance_id']);
            } else {
                $sql = "INSERT INTO workforce_attendance (employee_id, project_id, attendance_date, time_in, time_out, status, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iisssss", $employee_id, $record_project_id, $record_date, $time_in, $time_out, $status, $remarks);
            }
            if ($stmt->execute()) $saved++;
            $stmt->close();
        }
        $conn->commit();
        echo json_encode(['success' => true, 'message' => "$saved records saved"]);
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception($e->getMessage());
    }
}

function deleteAttendance($conn, $id) {
    if (!$id) throw new Exception('ID required');
    $stmt = $conn->prepare("DELETE FROM workforce_attendance WHERE attendance_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) echo json_encode(['success' => true, 'message' => 'Deleted']);
    else throw new Exception($stmt->error);
    $stmt->close();
}
?>