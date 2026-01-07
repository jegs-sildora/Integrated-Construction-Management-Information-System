<?php
/**
 * Employees API - Workforce Module
 * 
 * Handles CRUD operations for workforce employees
 */
header('Content-Type: application/json');

include __DIR__ . '/../project_context.php';

$conn = getWorkforceConnection();
$action = $_REQUEST['action'] ?? '';

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
            deleteEmployee($conn, $_REQUEST['id'] ?? 0);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();

// List all employees with optional project filter
function listEmployees($conn) {
    $project_id = $_REQUEST['project_id'] ?? null;
    $status = $_REQUEST['status'] ?? null;
    $search = $_REQUEST['search'] ?? '';
    
    $sql = "SELECT e.*, 
                   (SELECT COUNT(*) FROM workforce_assignments a WHERE a.employee_id = e.employee_id AND a.status = 'Active') as active_assignments
            FROM workforce_employees e
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if ($status && $status !== 'all') {
        $sql .= " AND e.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if ($search) {
        $sql .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ? OR e.email LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssss';
    }
    
    // Filter by project if specified
    if ($project_id) {
        $sql = "SELECT DISTINCT e.*, 
                       (SELECT COUNT(*) FROM workforce_assignments a WHERE a.employee_id = e.employee_id AND a.status = 'Active') as active_assignments,
                       wa.role
                FROM workforce_employees e
                JOIN workforce_assignments wa ON e.employee_id = wa.employee_id
                WHERE wa.project_id = ?";
        $params = [$project_id];
        $types = 'i';
        
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
    }
    
    $sql .= " ORDER BY e.last_name, e.first_name";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    $stmt->close();
    
    echo json_encode(['success' => true, 'data' => $employees]);
}

// Get single employee by ID
// DB Schema: workforce_employees (employee_id, employee_code, user_id, job_title_id, first_name, last_name, email, phone, status, hire_date)
function getEmployee($conn, $id) {
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT e.*, jt.title_name as job_title, jt.department, jt.default_daily_rate
            FROM workforce_employees e 
            LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
            WHERE e.employee_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'employee' => $row, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
    }
    $stmt->close();
}

// Create new employee
// DB Schema: workforce_employees (employee_id, employee_code, user_id, job_title_id, first_name, last_name, email, phone, status, hire_date)
function createEmployee($conn) {
    $data = $_POST;
    
    // Validate required fields
    if (empty($data['first_name']) || empty($data['last_name'])) {
        echo json_encode(['success' => false, 'message' => 'First name and last name are required']);
        return;
    }
    
    // Generate employee code if not provided (format: EMP-YYYY-XXX)
    if (empty($data['employee_code'])) {
        $year = date('Y');
        $result = $conn->query("SELECT MAX(employee_id) as max_id FROM workforce_employees");
        $row = $result->fetch_assoc();
        $next_id = ($row['max_id'] ?? 0) + 1;
        $data['employee_code'] = 'EMP-' . $year . '-' . str_pad($next_id, 3, '0', STR_PAD_LEFT);
    }
    
    $sql = "INSERT INTO workforce_employees (employee_code, job_title_id, first_name, last_name, email, phone, status, hire_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $status = $data['status'] ?? 'Active';
    $hire_date = !empty($data['hire_date']) ? $data['hire_date'] : date('Y-m-d');
    $job_title_id = !empty($data['job_title_id']) ? intval($data['job_title_id']) : null;
    
    $stmt->bind_param("sissssss", 
        $data['employee_code'],
        $job_title_id,
        $data['first_name'],
        $data['last_name'],
        $data['email'],
        $data['phone'],
        $status,
        $hire_date
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Employee created successfully',
            'id' => $conn->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create employee: ' . $stmt->error]);
    }
    $stmt->close();
}

// Update existing employee
// DB Schema: workforce_employees (employee_id, employee_code, user_id, job_title_id, first_name, last_name, email, phone, status, hire_date)
function updateEmployee($conn) {
    $data = $_POST;
    
    if (empty($data['employee_id'])) {
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }
    
    $sql = "UPDATE workforce_employees SET 
                first_name = ?,
                last_name = ?,
                email = ?,
                phone = ?,
                job_title_id = ?,
                status = ?,
                hire_date = ?
            WHERE employee_id = ?";
    
    $stmt = $conn->prepare($sql);
    $job_title_id = !empty($data['job_title_id']) ? intval($data['job_title_id']) : null;
    
    $stmt->bind_param("ssssissi",
        $data['first_name'],
        $data['last_name'],
        $data['email'],
        $data['phone'],
        $job_title_id,
        $data['status'],
        $data['hire_date'],
        $data['employee_id']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Employee updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update employee: ' . $stmt->error]);
    }
    $stmt->close();
}

// Delete employee
function deleteEmployee($conn, $id) {
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }
    
    // Check for active assignments
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM workforce_assignments WHERE employee_id = ? AND status = 'Active'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete employee with active assignments']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM workforce_employees WHERE employee_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Employee deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete employee: ' . $stmt->error]);
    }
    $stmt->close();
}
?>
