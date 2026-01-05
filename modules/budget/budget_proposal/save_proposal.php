<?php
// Suppress all warnings and errors to prevent HTML output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include config for database connection
require_once __DIR__ . '/../../config/config.php';

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset("utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
    
    // Check for JSON parsing errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data: ' . json_last_error_msg()]);
        exit;
    }
    
    $project_id = isset($data['project_id']) ? intval($data['project_id']) : 0;
    
    $title = isset($data['title']) ? trim($data['title']) : '';
    $target_phase = isset($data['target_phase']) ? trim($data['target_phase']) : null;
    $phase_start_date = isset($data['phase_start_date']) ? trim($data['phase_start_date']) : null;
    $phase_end_date = isset($data['phase_end_date']) ? trim($data['phase_end_date']) : null;
    $scope_description = isset($data['scope_description']) ? trim($data['scope_description']) : null;
    $items = isset($data['items']) ? $data['items'] : [];
    $status = isset($data['status']) ? trim($data['status']) : 'DRAFT';
    $total_amount = isset($data['total_amount']) ? floatval($data['total_amount']) : 0;
    
    // Use fixed user name as per enum definition in DB
    $user_name = 'JOHN DOE';
    
    // Validate required fields
    if ($project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
        exit;
    }
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Proposal title is required']);
        exit;
    }
    
    if (empty($items) || !is_array($items)) {
        echo json_encode(['success' => false, 'message' => 'At least one line item is required']);
        exit;
    }
    
    // Validate status
    $valid_statuses = ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED'];
    if (!in_array($status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid proposal status']);
        exit;
    }
    
    // Generate proposal code
    $year = date('Y');
    $sql = "SELECT COUNT(*) as count FROM budget_proposals WHERE YEAR(created_at) = $year";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $count = $row['count'] + 1;
    $code = "BP-" . $year . "-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    
    $description = !empty($scope_description) ? $scope_description : "Budget proposal with " . count($items) . " line items";
    
    // Validate each line item
    foreach ($items as $index => $item) {
        if (!isset($item['category']) || !isset($item['name']) || !isset($item['quantity']) || !isset($item['unitCost'])) {
            echo json_encode(['success' => false, 'message' => "Invalid data for line item #" . ($index + 1)]);
            exit;
        }
        
        $category = strtoupper(trim($item['category']));
        if (!in_array($category, ['MATERIALS', 'MATERIAL', 'LABOR', 'EQUIPMENT'])) {
            echo json_encode(['success' => false, 'message' => "Invalid category for line item #" . ($index + 1) . ": " . $item['category']]);
            exit;
        }
        
        if (floatval($item['quantity']) <= 0 || floatval($item['unitCost']) <= 0) {
            echo json_encode(['success' => false, 'message' => "Quantity and unit cost must be greater than zero for line item #" . ($index + 1)]);
            exit;
        }
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // FIX: TEMPORARILY DISABLE FOREIGN KEY CHECKS
        // This allows us to insert the project_id from the MAIN database (icmis.projects)
        // into this BUDGET database table, even if the local constraint check fails.
        $conn->query("SET FOREIGN_KEY_CHECKS=0");

        // Insert budget proposal
        $stmt = $conn->prepare("INSERT INTO budget_proposals (project_id, code, title, description, target_phase, phase_start_date, phase_end_date, scope_description, total_amount, status, user_name, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("isssssssdss", $project_id, $code, $title, $description, $target_phase, $phase_start_date, $phase_end_date, $scope_description, $total_amount, $status, $user_name);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert budget proposal: " . $stmt->error);
        }
        
        $proposal_id = $conn->insert_id;
        
        // Insert budget line items
        $stmt_items = $conn->prepare("INSERT INTO budget_line_items (proposal_id, category, item_name, quantity, unit_cost, duration, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $items_inserted = 0;
        foreach ($items as $item) {
            // Map category names
            $category = strtoupper(trim($item['category']));
            if ($category === 'MATERIALS') {
                $category = 'MATERIAL';
            }
            
            $item_name = trim($item['name']);
            $quantity = floatval($item['quantity']);
            $unit_cost = floatval($item['unitCost']);
            $duration = 1.00; // Default duration
            $subtotal = floatval($item['subtotal']);
            
            $stmt_items->bind_param("issdddd", $proposal_id, $category, $item_name, $quantity, $unit_cost, $duration, $subtotal);
            
            if (!$stmt_items->execute()) {
                throw new Exception("Failed to insert budget line item: " . $stmt_items->error);
            }
            $items_inserted++;
        }
        
        // FIX: RE-ENABLE FOREIGN KEY CHECKS BEFORE COMMIT
        $conn->query("SET FOREIGN_KEY_CHECKS=1");

        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Proposal saved successfully',
            'proposal_id' => $proposal_id,
            'code' => $code,
            'items_saved' => $items_inserted,
            'total_amount' => number_format($total_amount, 2),
            'status' => $status
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        // Ensure checks are back on even if failed
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    } catch (Exception $e) {
        // Handle any unexpected errors
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>