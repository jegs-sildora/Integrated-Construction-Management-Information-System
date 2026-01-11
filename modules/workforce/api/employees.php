<?php
/**
 * Employees API - Workforce Module
 */
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include __DIR__ . '/../project_context.php';
require_once __DIR__ . '/../../../core/Logger.php';

$conn = getWorkforceConnection();

// 1. Handle JSON Input if $_POST is empty
if (empty($_POST)) {
    $rawInput = file_get_contents('php://input');
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $_POST = $decoded;
    }
}

// 2. Action Detection (FIXED PRIORITY)
// We prioritize $_GET because the JavaScript explicitly calculates the correct action 
// and puts it in the URL. The $_POST hidden input might contain stale data.
$action = '';

if (isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif (isset($_POST['action'])) {
    $action = $_POST['action'];
} elseif (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
}

$action = trim($action);

// Double-safety: If action is 'update' but we have no ID, force 'create'
if ($action === 'update' && empty($_POST['employee_id'])) {
    $action = 'create';
}

try {
    switch ($action) {
        case 'list':
            listEmployees($conn);
            break;
        case 'get':
            getEmployee($conn, $_REQUEST['id'] ?? 0);
            break;
        case 'create':
            createEmployee($conn);
            break;
        case 'update':
            updateEmployee($conn);
            break;
        case 'delete':
            // Prefer POST (including JSON-decoded body assigned to $_POST) then fall back to REQUEST
            $idToDelete = $_POST['employee_id'] ?? $_POST['id'] ?? $_REQUEST['employee_id'] ?? $_REQUEST['id'] ?? 0;
            deleteEmployee($conn, $idToDelete);
            break;
        default:
            // Only encode POST if it exists to avoid errors
            $debug_post = isset($_POST) ? json_encode($_POST) : '[]'; 
            echo json_encode([
                'success' => false, 
                'message' => "Invalid action. Server received: '{$action}'. POST Data: {$debug_post}"
            ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();

// --- Helper Functions ---

function resolveJobTitleId($conn, $idInput, $nameInput) {
    if (!empty($idInput) && is_numeric($idInput)) return intval($idInput);
    if (!empty($nameInput)) {
        $stmt = $conn->prepare("SELECT job_title_id FROM workforce_job_titles WHERE title_name = ? LIMIT 1");
        $stmt->bind_param("s", $nameInput);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) return intval($row['job_title_id']);
    }
    return null; 
}

function cleanCurrency($val) {
    if (empty($val)) return 0.00;
    return floatval(str_replace(',', '', $val));
}

// --- API Functions ---

function listEmployees($conn) {
    $project_id = $_REQUEST['project_id'] ?? null;
    $status = $_REQUEST['status'] ?? null;
    $search = $_REQUEST['search'] ?? '';
    
    $sql = "SELECT e.employee_id, e.employee_code, e.first_name, e.last_name, e.email, e.phone, 
                   e.status, e.job_title_id, jt.title_name as position, jt.department,
                   jt.default_daily_rate as default_rate
            FROM workforce_employees e
            LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if ($status && $status !== 'all') {
        $sql .= " AND e.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    if ($search) {
        $sql .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
        $types .= 'sss';
    }
    
    if ($project_id) {
        $sql = "SELECT DISTINCT e.employee_id, e.employee_code, e.first_name, e.last_name, 
                       jt.title_name as position, wa.role, e.status
                FROM workforce_employees e
                LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
                JOIN workforce_assignments wa ON e.employee_id = wa.employee_id
                WHERE wa.project_id = ?";
        $params = [$project_id];
        $types = 'i';
    }
    
    $sql .= " ORDER BY e.last_name, e.first_name";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $employees]);
}

function getEmployee($conn, $id) {
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT e.*, jt.title_name as position_name, jt.department 
                            FROM workforce_employees e 
                            LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
                            WHERE e.employee_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $row['position'] = $row['position_name']; 
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
    }
    $stmt->close();
}

function createEmployee($conn) {
    $data = $_POST;
    if (empty($data['first_name']) || empty($data['last_name'])) {
        echo json_encode(['success' => false, 'message' => 'First name and last name are required']);
        return;
    }
    
    if (empty($data['employee_code'])) {
        $year = date('Y');
        $result = $conn->query("SELECT MAX(employee_id) as max_id FROM workforce_employees");
        $row = $result->fetch_assoc();
        $next_id = ($row['max_id'] ?? 0) + 1;
        $data['employee_code'] = 'EMP-' . $year . '-' . str_pad($next_id, 3, '0', STR_PAD_LEFT);
    }

    $job_title_id = resolveJobTitleId($conn, $data['job_title_id'] ?? null, $data['position'] ?? '');
    $supervisor_id = !empty($data['supervisor_id']) ? intval($data['supervisor_id']) : null;
    $daily_rate = cleanCurrency($data['daily_rate'] ?? 0);
    $monthly_salary = cleanCurrency($data['monthly_salary'] ?? 0);
    $birthday = !empty($data['birthday']) ? $data['birthday'] : null;
    $hire_date = !empty($data['hire_date']) ? $data['hire_date'] : date('Y-m-d');

    $sql = "INSERT INTO workforce_employees (
                employee_code, first_name, last_name, suffix, gender, birthday, email, phone, address,
                job_title_id, employment_type, payment_type, daily_rate, monthly_salary,
                bank_name, bank_account, emergency_contact_name, emergency_contact_phone,
                supervisor_id, notes, status, hire_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Database Error: " . $conn->error);

    $stmt->bind_param("sssssssssissddssssisss", 
        $data['employee_code'], $data['first_name'], $data['last_name'], $data['suffix'], $data['gender'],
        $birthday, $data['email'], $data['phone'], $data['address'], $job_title_id, 
        $data['employment_type'], $data['payment_type'], $daily_rate, $monthly_salary,
        $data['bank_name'], $data['bank_account'], $data['emergency_contact_name'], $data['emergency_contact_phone'],
        $supervisor_id, $data['notes'], $data['status'], $hire_date
    );
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        Logger::init($conn);
        Logger::create('Workforce', "Employee Created: {$data['first_name']} {$data['last_name']} ({$data['employee_code']})", $newId);
        echo json_encode(['success' => true, 'message' => 'Employee created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create: ' . $stmt->error]);
    }
}

function updateEmployee($conn) {
    $data = $_POST;
    
    // Fallback: If no ID is present, treat this as a Create request
    if (empty($data['employee_id'])) {
        createEmployee($conn);
        return;
    }
    
    $job_title_id = resolveJobTitleId($conn, $data['job_title_id'] ?? null, $data['position'] ?? '');
    $supervisor_id = !empty($data['supervisor_id']) ? intval($data['supervisor_id']) : null;
    $daily_rate = cleanCurrency($data['daily_rate'] ?? 0);
    $monthly_salary = cleanCurrency($data['monthly_salary'] ?? 0);
    $birthday = !empty($data['birthday']) ? $data['birthday'] : null;
    
    $sql = "UPDATE workforce_employees SET 
                first_name = ?, last_name = ?, suffix = ?, gender = ?, birthday = ?, email = ?, phone = ?, address = ?,
                job_title_id = ?, employment_type = ?, payment_type = ?, daily_rate = ?, monthly_salary = ?,
                bank_name = ?, bank_account = ?, emergency_contact_name = ?, emergency_contact_phone = ?,
                supervisor_id = ?, notes = ?, status = ?, hire_date = ?
            WHERE employee_id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Database Error: " . $conn->error);
    
    $stmt->bind_param("ssssssssissddsssssissi",
        $data['first_name'], $data['last_name'], $data['suffix'], $data['gender'], $birthday, $data['email'],
        $data['phone'], $data['address'], $job_title_id, $data['employment_type'], $data['payment_type'],
        $daily_rate, $monthly_salary, $data['bank_name'], $data['bank_account'],
        $data['emergency_contact_name'], $data['emergency_contact_phone'], $supervisor_id,
        $data['notes'], $data['status'], $data['hire_date'], $data['employee_id']
    );
    
    if ($stmt->execute()) {
        Logger::init($conn);
        Logger::update('Workforce', "Employee Updated: {$data['first_name']} {$data['last_name']}", intval($data['employee_id']));
        echo json_encode(['success' => true, 'message' => 'Employee updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update: ' . $stmt->error]);
    }
}

function deleteEmployee($conn, $id) {
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM workforce_assignments WHERE employee_id = ? AND status = 'Active'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete employee with active assignments.']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM workforce_employees WHERE employee_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        Logger::init($conn);
        Logger::delete('Workforce', "Employee Deleted: ID #$id", intval($id));
        echo json_encode(['success' => true, 'message' => 'Employee deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete: ' . $stmt->error]);
    }
}
?>