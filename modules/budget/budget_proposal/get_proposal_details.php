<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include __DIR__ . '/../connection.php';

// Check if connection was successful
if (!isset($conn) || !$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Proposal ID is required']);
    exit;
}

$proposal_id = intval($_GET['id']);

if ($proposal_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid proposal ID']);
    exit;
}

try {
    // Fetch proposal details with project information
    $sql = "SELECT bp.*, p.name as project_name, p.project_code 
            FROM budget_proposals bp 
            LEFT JOIN projects p ON bp.project_id = p.project_id 
            WHERE bp.proposal_id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $proposal_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Proposal not found']);
        exit;
    }
    
    $proposal = $result->fetch_assoc();
    
    // Format created_at date
    $proposal['created_at'] = date('M d, Y', strtotime($proposal['created_at']));
    
    // Fetch line items
    $sql_items = "SELECT * FROM budget_line_items WHERE proposal_id = ? ORDER BY line_item_id ASC";
    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->bind_param("i", $proposal_id);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    
    $items = [];
    while ($row = $result_items->fetch_assoc()) {
        $items[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'proposal' => $proposal,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching proposal details: ' . $e->getMessage(),
        'error_type' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

if (isset($conn) && $conn) {
    $conn->close();
}
?>
