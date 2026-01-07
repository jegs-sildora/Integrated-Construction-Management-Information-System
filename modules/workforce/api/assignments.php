<?php
/**
 * Assignments API - Workforce Module
 * 
 * Handles CRUD operations for workforce project assignments
 */
header('Content-Type: application/json');

include __DIR__ . '/../project_context.php';

$conn = getWorkforceConnection();
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

// List assignments with optional filters
function listAssignments($conn) {
    $project_id = $_REQUEST['project_id'] ?? null;
    $status = $_REQUEST['status'] ?? null;
    $employee_id = $_REQUEST['employee_id'] ?? null;
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
    
    if ($project_id) {
        $sql .= " AND a.project_id = ?";
        $params[] = $project_id;
        $types .= 'i';
    }
    
    if ($status && $status !== 'all') {
        $sql .= " AND a.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if ($employee_id) {
        $sql .= " AND a.employee_id = ?";
        $params[] = $employee_id;
        $types .= 'i';
    }
    
    if ($search) {
        $sql .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ? OR a.role LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssss';
    }
    
    $sql .= " ORDER BY a.start_date DESC, e.last_name, e.first_name";
    
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

// Get single assignment by ID
// DB Schema: workforce_assignments (assignment_id, employee_id, project_id, phase_id, role, task_description, start_date, end_date, status)
function getAssignment($conn, $id) {
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Assignment ID required']);
        return;
    }
    
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
        echo json_encode(['success' => true, 'assignment' => $row, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
    }
    $stmt->close();
}

// Create new assignment
// DB Schema: workforce_assignments (assignment_id, employee_id, project_id, phase_id, role, task_description, start_date, end_date, status)
// status ENUM: 'Active','Completed','Cancelled'
function createAssignment($conn) {
    $data = $_POST;
    
    if (empty($data['employee_id']) || empty($data['project_id'])) {
        echo json_encode(['success' => false, 'message' => 'Employee and project are required']);
        return;
    }
    
    // Check for existing active assignment
    $stmt = $conn->prepare("SELECT assignment_id FROM workforce_assignments 
                           WHERE employee_id = ? AND project_id = ? AND status = 'Active'");
    $stmt->bind_param("ii", $data['employee_id'], $data['project_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Employee already has an active assignment to this project']);
        $stmt->close();
        return;
    }
    $stmt->close();
    
    $sql = "INSERT INTO workforce_assignments 
                (employee_id, project_id, phase_id, role, task_description, start_date, end_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $phase_id = !empty($data['phase_id']) ? intval($data['phase_id']) : null;
    $status = $data['status'] ?? 'Active';
    $task_description = $data['task_description'] ?? null;
    
    $stmt->bind_param("iiisssss",
        $data['employee_id'],
        $data['project_id'],
        $phase_id,
        $data['role'],
        $task_description,
        $data['start_date'],
        $data['end_date'],
        $status
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Assignment created successfully',
            'id' => $conn->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create assignment: ' . $stmt->error]);
    }
    $stmt->close();
}

// Update existing assignment
// DB Schema: workforce_assignments (assignment_id, employee_id, project_id, phase_id, role, task_description, start_date, end_date, status)
function updateAssignment($conn) {
    $data = $_POST;
    
    if (empty($data['assignment_id'])) {
        echo json_encode(['success' => false, 'message' => 'Assignment ID required']);
        return;
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
    
    $stmt->bind_param("iiisssssi",
        $data['employee_id'],
        $data['project_id'],
        $phase_id,
        $data['role'],
        $task_description,
        $data['start_date'],
        $data['end_date'],
        $data['status'],
        $data['assignment_id']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Assignment updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update assignment: ' . $stmt->error]);
    }
    $stmt->close();
}

// Delete assignment
function deleteAssignment($conn, $id) {
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Assignment ID required']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM workforce_assignments WHERE assignment_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Assignment deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete assignment: ' . $stmt->error]);
    }
    $stmt->close();
}

// Get phases for a project (for dropdown)
function getPhases($conn, $project_id) {
    if (!$project_id) {
        echo json_encode(['success' => true, 'data' => []]);
        return;
    }
    
    $stmt = $conn->prepare("SELECT phase_id, phase_name FROM icmis_project_phases WHERE project_id = ? ORDER BY phase_id");
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
