<?php
// Start output buffering to prevent any accidental output
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Clear any output buffer before including connection
ob_clean();

// Include config for database connection
require_once __DIR__ . '/../../../config/config.php';

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error_details' => ['error' => $conn->connect_error]
    ]);
    ob_end_flush();
    exit();
}
$conn->set_charset("utf8mb4");

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'proposal_id' => null
];

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method. Only POST is allowed.');
    }

    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validate JSON decoding
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    // Validate required fields
    if (!isset($data['proposal_id']) || empty($data['proposal_id'])) {
        throw new Exception('Proposal ID is required');
    }

    if (!isset($data['project_id']) || empty($data['project_id'])) {
        throw new Exception('Project ID is required');
    }

    if (!isset($data['title']) || empty(trim($data['title']))) {
        throw new Exception('Proposal title is required');
    }

    if (!isset($data['items']) || !is_array($data['items'])) {
        throw new Exception('Items array is required');
    }

    if (count($data['items']) === 0) {
        throw new Exception('At least one line item is required');
    }

    if (!isset($data['status']) || empty($data['status'])) {
        throw new Exception('Status is required');
    }

    if (!isset($data['total_amount'])) {
        throw new Exception('Total amount is required');
    }

    if ($data['total_amount'] <= 0) {
        throw new Exception('Total amount must be greater than zero');
    }

    // Sanitize inputs
    $proposal_id = intval($data['proposal_id']);
    $project_id = intval($data['project_id']);
    $title = trim($data['title']);
    $target_phase = isset($data['target_phase']) ? trim($data['target_phase']) : null;
    $phase_start_date = isset($data['phase_start_date']) ? trim($data['phase_start_date']) : null;
    $phase_end_date = isset($data['phase_end_date']) ? trim($data['phase_end_date']) : null;
    $scope_description = isset($data['scope_description']) ? trim($data['scope_description']) : null;
    $status = strtoupper(trim($data['status']));
    $total_amount = floatval($data['total_amount']);
    $items = $data['items'];

    // Validate status enum
    $valid_statuses = ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid status. Must be one of: ' . implode(', ', $valid_statuses));
    }

    // Verify proposal exists
    $check_sql = "SELECT proposal_id FROM budget_proposals WHERE proposal_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    
    $check_stmt->bind_param("i", $proposal_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception('Proposal not found');
    }
    $check_stmt->close();

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update budget_proposals table
        $update_sql = "UPDATE budget_proposals 
                      SET project_id = ?, 
                          title = ?, 
                          target_phase = ?,
                          phase_start_date = ?,
                          phase_end_date = ?,
                          scope_description = ?,
                          status = ?, 
                          total_amount = ?,
                          updated_at = CURRENT_TIMESTAMP 
                      WHERE proposal_id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        if (!$update_stmt) {
            throw new Exception('Failed to prepare update statement: ' . $conn->error);
        }

        $update_stmt->bind_param("issssssdi", $project_id, $title, $target_phase, $phase_start_date, $phase_end_date, $scope_description, $status, $total_amount, $proposal_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception('Failed to update proposal: ' . $update_stmt->error);
        }
        $update_stmt->close();

        // Delete existing line items
        $delete_sql = "DELETE FROM budget_line_items WHERE proposal_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        if (!$delete_stmt) {
            throw new Exception('Failed to prepare delete statement: ' . $conn->error);
        }

        $delete_stmt->bind_param("i", $proposal_id);
        if (!$delete_stmt->execute()) {
            throw new Exception('Failed to delete old line items: ' . $delete_stmt->error);
        }
        $delete_stmt->close();

        // Insert new line items
        $item_sql = "INSERT INTO budget_line_items 
                    (proposal_id, category, item_name, quantity, unit_cost, subtotal) 
                    VALUES (?, ?, ?, ?, ?, ?)";
        
        $item_stmt = $conn->prepare($item_sql);
        if (!$item_stmt) {
            throw new Exception('Failed to prepare line item insert statement: ' . $conn->error);
        }

        $items_saved = 0;
        foreach ($items as $item) {
            // Validate item data
            if (!isset($item['category']) || !isset($item['name']) || 
                !isset($item['quantity']) || !isset($item['unitCost'])) {
                throw new Exception('Invalid item data structure');
            }

            // Map category names to database enum values
            $categoryMap = [
                'materials' => 'MATERIAL',
                'labor' => 'LABOR',
                'equipment' => 'EQUIPMENT'
            ];

            $item_category = strtolower($item['category']);
            if (!isset($categoryMap[$item_category])) {
                throw new Exception('Invalid category: ' . $item['category']);
            }
            
            $category = $categoryMap[$item_category];
            $item_name = trim($item['name']);
            $quantity = floatval($item['quantity']);
            $unit_cost = floatval($item['unitCost']);
            $subtotal = $quantity * $unit_cost;

            // Validate item values
            if (empty($item_name)) {
                throw new Exception('Item name cannot be empty');
            }
            if ($quantity <= 0) {
                throw new Exception('Quantity must be greater than zero for item: ' . $item_name);
            }
            if ($unit_cost <= 0) {
                throw new Exception('Unit cost must be greater than zero for item: ' . $item_name);
            }

            $item_stmt->bind_param("issddd", $proposal_id, $category, $item_name, $quantity, $unit_cost, $subtotal);
            
            if (!$item_stmt->execute()) {
                throw new Exception('Failed to insert line item: ' . $item_stmt->error);
            }
            
            $items_saved++;
        }
        $item_stmt->close();

        // Commit transaction
        $conn->commit();

        // Success response
        $response['success'] = true;
        $response['message'] = 'Budget proposal updated successfully';
        $response['proposal_id'] = $proposal_id;
        $response['items_saved'] = $items_saved;

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['error_details'] = [
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    
    // Log the full error for debugging
    error_log("Update Proposal Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
}

// Close database connection
if (isset($conn) && $conn) {
    $conn->close();
}

// Clear any output buffer and send clean JSON
ob_clean();
$json_output = json_encode($response);

// Check if JSON encoding was successful
if ($json_output === false) {
    $json_output = json_encode([
        'success' => false,
        'message' => 'JSON encoding error',
        'error_details' => ['json_error' => json_last_error_msg()]
    ]);
}

echo $json_output;
ob_end_flush();
?>