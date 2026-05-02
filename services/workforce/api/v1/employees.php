<?php
/**
 * Employees API v1 - Workforce Service
 */
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../Logger.php';

$db = Database::getConnection();

// Handle JSON Input
if (empty($_POST)) {
    $rawInput = file_get_contents('php://input');
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $_POST = $decoded;
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? $_REQUEST['action'] ?? 'list';
$action = trim($action);

if ($action === 'update' && empty($_POST['employee_id'])) {
    $action = 'create';
}

try {
    switch ($action) {
        case 'list':
            listEmployees($db);
            break;
        case 'get':
            getEmployee($db, $_REQUEST['id'] ?? 0);
            break;
        case 'create':
            createEmployee($db);
            break;
        case 'update':
            updateEmployee($db);
            break;
        case 'delete':
            $idToDelete = $_POST['employee_id'] ?? $_POST['id'] ?? $_REQUEST['employee_id'] ?? $_REQUEST['id'] ?? 0;
            deleteEmployee($db, $idToDelete);
            break;
        default:
            // Default to list if action is unknown but present? No, let's keep it strict if provided.
            if (empty($action)) {
                listEmployees($db);
            } else {
                echo json_encode(['success' => false, 'message' => "Invalid action: '{$action}'"]);
            }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// --- Helper Functions ---

function resolveJobTitleId($db, $idInput, $nameInput) {
    if (!empty($idInput) && is_numeric($idInput)) return intval($idInput);
    if (!empty($nameInput)) {
        $stmt = $db->prepare("SELECT job_title_id FROM job_titles WHERE title_name = ? LIMIT 1");
        $stmt->execute([$nameInput]);
        if ($row = $stmt->fetch()) return intval($row['job_title_id']);
    }
    return null; 
}

function cleanCurrency($val) {
    if (empty($val)) return 0.00;
    if (is_numeric($val)) return floatval($val);
    return floatval(str_replace(',', '', $val));
}

// --- API Functions ---

function listEmployees($db) {
    $project_id = $_REQUEST['project_id'] ?? null;
    $status = $_REQUEST['status'] ?? null;
    $search = $_REQUEST['search'] ?? '';
    
    $params = [];
    
    if ($project_id) {
        // Remove hard join across bounded contexts if projects were in another service, 
        // but since this is just a list of employees assigned to a project, 
        // we can still join assignments (local to this service).
        $sql = "SELECT DISTINCT e.employee_id, e.employee_code, e.first_name, e.last_name, 
                       jt.title_name as position, wa.role, e.status
                FROM employees e
                LEFT JOIN job_titles jt ON e.job_title_id = jt.job_title_id
                JOIN assignments wa ON e.employee_id = wa.employee_id
                WHERE wa.project_id = ?";
        $params[] = $project_id;
    } else {
        $sql = "SELECT e.employee_id, e.employee_code, e.first_name, e.last_name, e.email, e.phone, 
                       e.status, e.job_title_id, jt.title_name as position, jt.department,
                       jt.default_daily_rate as default_rate
                FROM employees e
                LEFT JOIN job_titles jt ON e.job_title_id = jt.job_title_id
                WHERE 1=1";
        
        if ($status && $status !== 'all') {
            $sql .= " AND e.status = ?";
            $params[] = $status;
        }
        if ($search) {
            $sql .= " AND (e.first_name ILIKE ? OR e.last_name ILIKE ? OR e.employee_code ILIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
    }
    
    $sql .= " ORDER BY e.last_name, e.first_name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $employees = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $employees]);
}

function getEmployee($db, $id) {
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }
    
    $stmt = $db->prepare("SELECT e.*, jt.title_name as position_name, jt.department 
                            FROM employees e 
                            LEFT JOIN job_titles jt ON e.job_title_id = jt.job_title_id
                            WHERE e.employee_id = ?");
    $stmt->execute([$id]);
    
    if ($row = $stmt->fetch()) {
        $row['position'] = $row['position_name']; 
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
    }
}

function createEmployee($db) {
    $data = $_POST;
    if (empty($data['first_name']) || empty($data['last_name'])) {
        echo json_encode(['success' => false, 'message' => 'First name and last name are required']);
        return;
    }
    
    if (empty($data['employee_code'])) {
        $year = date('Y');
        $stmt = $db->query("SELECT MAX(employee_id) as max_id FROM employees");
        $row = $stmt->fetch();
        $next_id = ($row['max_id'] ?? 0) + 1;
        $data['employee_code'] = 'EMP-' . $year . '-' . str_pad($next_id, 3, '0', STR_PAD_LEFT);
    }

    $job_title_id = resolveJobTitleId($db, $data['job_title_id'] ?? null, $data['position'] ?? '');
    $supervisor_id = !empty($data['supervisor_id']) ? intval($data['supervisor_id']) : null;
    $daily_rate = cleanCurrency($data['daily_rate'] ?? 0);
    $monthly_salary = cleanCurrency($data['monthly_salary'] ?? 0);
    $birthday = !empty($data['birthday']) ? $data['birthday'] : null;
    $hire_date = !empty($data['hire_date']) ? $data['hire_date'] : date('Y-m-d');

    $sql = "INSERT INTO employees (
                employee_code, first_name, last_name, suffix, gender, birthday, email, phone, address,
                job_title_id, employment_type, payment_type, daily_rate, monthly_salary,
                bank_name, bank_account, emergency_contact_name, emergency_contact_phone,
                supervisor_id, notes, status, hire_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    
    $params = [
        $data['employee_code'], $data['first_name'], $data['last_name'], $data['suffix'] ?? null, $data['gender'] ?? null,
        $birthday, $data['email'] ?? null, $data['phone'] ?? null, $data['address'] ?? null, $job_title_id, 
        $data['employment_type'] ?? 'Regular', $data['payment_type'] ?? 'Daily', $daily_rate, $monthly_salary,
        $data['bank_name'] ?? null, $data['bank_account'] ?? null, $data['emergency_contact_name'] ?? null, $data['emergency_contact_phone'] ?? null,
        $supervisor_id, $data['notes'] ?? null, $data['status'] ?? 'Active', $hire_date
    ];
    
    if ($stmt->execute($params)) {
        $newId = $db->lastInsertId();
        Logger::create('Workforce', "Employee Created: {$data['first_name']} {$data['last_name']} ({$data['employee_code']})", $newId);
        echo json_encode(['success' => true, 'message' => 'Employee created successfully', 'id' => $newId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create employee']);
    }
}

function updateEmployee($db) {
    $data = $_POST;
    
    if (empty($data['employee_id'])) {
        createEmployee($db);
        return;
    }
    
    $job_title_id = resolveJobTitleId($db, $data['job_title_id'] ?? null, $data['position'] ?? '');
    $supervisor_id = !empty($data['supervisor_id']) ? intval($data['supervisor_id']) : null;
    $daily_rate = cleanCurrency($data['daily_rate'] ?? 0);
    $monthly_salary = cleanCurrency($data['monthly_salary'] ?? 0);
    $birthday = !empty($data['birthday']) ? $data['birthday'] : null;
    
    $sql = "UPDATE employees SET 
                first_name = ?, last_name = ?, suffix = ?, gender = ?, birthday = ?, email = ?, phone = ?, address = ?,
                job_title_id = ?, employment_type = ?, payment_type = ?, daily_rate = ?, monthly_salary = ?,
                bank_name = ?, bank_account = ?, emergency_contact_name = ?, emergency_contact_phone = ?,
                supervisor_id = ?, notes = ?, status = ?, hire_date = ?
            WHERE employee_id = ?";
    
    $stmt = $db->prepare($sql);
    
    $params = [
        $data['first_name'], $data['last_name'], $data['suffix'] ?? null, $data['gender'] ?? null, $birthday, $data['email'] ?? null,
        $data['phone'] ?? null, $data['address'] ?? null, $job_title_id, $data['employment_type'] ?? 'Regular', $data['payment_type'] ?? 'Daily',
        $daily_rate, $monthly_salary, $data['bank_name'] ?? null, $data['bank_account'] ?? null,
        $data['emergency_contact_name'] ?? null, $data['emergency_contact_phone'] ?? null, $supervisor_id,
        $data['notes'] ?? null, $data['status'] ?? 'Active', $data['hire_date'] ?? date('Y-m-d'), $data['employee_id']
    ];
    
    if ($stmt->execute($params)) {
        Logger::update('Workforce', "Employee Updated: {$data['first_name']} {$data['last_name']}", intval($data['employee_id']));
        echo json_encode(['success' => true, 'message' => 'Employee updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update employee']);
    }
}

function deleteEmployee($db, $id) {
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM assignments WHERE employee_id = ? AND status = 'Active'");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    
    if ($row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete employee with active assignments.']);
        return;
    }
    
    $stmt = $db->prepare("DELETE FROM employees WHERE employee_id = ?");
    if ($stmt->execute([$id])) {
        Logger::delete('Workforce', "Employee Deleted: ID #$id", intval($id));
        echo json_encode(['success' => true, 'message' => 'Employee deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete employee']);
    }
}
