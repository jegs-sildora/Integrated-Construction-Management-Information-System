<?php
/**
 * Attendance API - Workforce Module
 * 
 * Handles attendance tracking operations
 */
header('Content-Type: application/json');

include __DIR__ . '/../project_context.php';

$conn = getWorkforceConnection();
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

// List attendance records with filters
function listAttendance($conn) {
    $project_id = $_REQUEST['project_id'] ?? null;
    $date = $_REQUEST['date'] ?? date('Y-m-d');
    $employee_id = $_REQUEST['employee_id'] ?? null;
    
    $sql = "SELECT att.*, 
                   e.employee_code, e.first_name, e.last_name
            FROM workforce_attendance att
            JOIN workforce_employees e ON att.employee_id = e.employee_id
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if ($project_id) {
        $sql .= " AND att.project_id = ?";
        $params[] = $project_id;
        $types .= 'i';
    }
    
    if ($date) {
        $sql .= " AND att.attendance_date = ?";
        $params[] = $date;
        $types .= 's';
    }
    
    if ($employee_id) {
        $sql .= " AND att.employee_id = ?";
        $params[] = $employee_id;
        $types .= 'i';
    }
    
    $sql .= " ORDER BY e.last_name, e.first_name";
    
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
}

// Get attendance for specific employee and date
function getAttendance($conn) {
    $employee_id = $_REQUEST['employee_id'] ?? 0;
    $project_id = $_REQUEST['project_id'] ?? 0;
    $date = $_REQUEST['date'] ?? date('Y-m-d');
    
    $stmt = $conn->prepare("SELECT * FROM workforce_attendance 
                           WHERE employee_id = ? AND project_id = ? AND attendance_date = ?");
    $stmt->bind_param("iis", $employee_id, $project_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => true, 'data' => null]);
    }
    $stmt->close();
}

// Save single attendance record
function saveAttendance($conn) {
    $data = $_POST;
    
    if (empty($data['employee_id']) || empty($data['project_id']) || empty($data['attendance_date'])) {
        echo json_encode(['success' => false, 'message' => 'Employee, project, and date are required']);
        return;
    }
    
    // Check if record exists
    $stmt = $conn->prepare("SELECT attendance_id FROM workforce_attendance 
                           WHERE employee_id = ? AND project_id = ? AND attendance_date = ?");
    $stmt->bind_param("iis", $data['employee_id'], $data['project_id'], $data['attendance_date']);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();
    
    if ($existing) {
        // Update existing record
        $sql = "UPDATE workforce_attendance SET 
                    time_in = ?, time_out = ?, status = ?, remarks = ?
                WHERE attendance_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi",
            $data['time_in'],
            $data['time_out'],
            $data['status'],
            $data['remarks'],
            $existing['attendance_id']
        );
    } else {
        // Insert new record
        $sql = "INSERT INTO workforce_attendance 
                    (employee_id, project_id, attendance_date, time_in, time_out, status, remarks)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssss",
            $data['employee_id'],
            $data['project_id'],
            $data['attendance_date'],
            $data['time_in'],
            $data['time_out'],
            $data['status'],
            $data['remarks']
        );
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Attendance saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save attendance: ' . $stmt->error]);
    }
    $stmt->close();
}

// Save bulk attendance records
function saveBulkAttendance($conn) {
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);
    
    if (empty($data['records']) || empty($data['project_id']) || empty($data['date'])) {
        echo json_encode(['success' => false, 'message' => 'Records, project ID, and date are required']);
        return;
    }
    
    $project_id = $data['project_id'];
    $date = $data['date'];
    $records = $data['records'];
    
    $conn->begin_transaction();
    
    try {
        $saved = 0;
        
        foreach ($records as $record) {
            $employee_id = $record['employee_id'];
            $time_in = $record['time_in'] ?? null;
            $time_out = $record['time_out'] ?? null;
            $status = $record['status'] ?? 'Present';
            $remarks = $record['remarks'] ?? '';
            
            // Check if record exists
            $stmt = $conn->prepare("SELECT attendance_id FROM workforce_attendance 
                                   WHERE employee_id = ? AND project_id = ? AND attendance_date = ?");
            $stmt->bind_param("iis", $employee_id, $project_id, $date);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing = $result->fetch_assoc();
            $stmt->close();
            
            if ($existing) {
                $sql = "UPDATE workforce_attendance SET 
                            time_in = ?, time_out = ?, status = ?, remarks = ?
                        WHERE attendance_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $time_in, $time_out, $status, $remarks, $existing['attendance_id']);
            } else {
                $sql = "INSERT INTO workforce_attendance 
                            (employee_id, project_id, attendance_date, time_in, time_out, status, remarks)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iisssss", $employee_id, $project_id, $date, $time_in, $time_out, $status, $remarks);
            }
            
            if ($stmt->execute()) {
                $saved++;
            }
            $stmt->close();
        }
        
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => "$saved attendance records saved successfully"
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to save attendance: ' . $e->getMessage()]);
    }
}

// Delete attendance record
function deleteAttendance($conn, $id) {
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Attendance ID required']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM workforce_attendance WHERE attendance_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Attendance record deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete attendance']);
    }
    $stmt->close();
}
?>
