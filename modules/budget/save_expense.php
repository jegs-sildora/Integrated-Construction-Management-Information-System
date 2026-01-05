<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

// Include config for database connection
require_once __DIR__ . '/../../config/config.php';

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset("utf8mb4");

// Enable error reporting for debugging
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // Verify request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    $required_fields = ['project_id', 'expense_date', 'phase', 'supplier_name', 'total_amount', 'status', 'line_items'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception("Missing required field: " . ucfirst(str_replace('_', ' ', $field)));
        }
    }

    // Sanitize and validate inputs
    $project_id = intval($_POST['project_id']);
    $expense_date = trim($_POST['expense_date']);
    $phase = trim($_POST['phase']);
    $supplier_name = trim($_POST['supplier_name']);
    $total_amount = floatval($_POST['total_amount']);
    $status = trim($_POST['status']);
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
    
    // Parse line items
    $line_items = json_decode($_POST['line_items'], true);
    if (!is_array($line_items) || empty($line_items)) {
        throw new Exception('No expense items provided');
    }

    // Validate project_id
    if ($project_id <= 0) {
        throw new Exception('Invalid project ID');
    }

    // Validate expense_date format
    $date_obj = DateTime::createFromFormat('Y-m-d', $expense_date);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $expense_date) {
        throw new Exception('Invalid date format');
    }

    // Check if date is not in the future using string comparison
    $today_str = date('Y-m-d');
    if ($expense_date > $today_str) {
        throw new Exception('Expense date cannot be in the future');
    }

    // Validate phase
    $valid_phases = ['Phase 1: Mobilization', 'Phase 2: Structural', 'Phase 3: MEPFS', 'Phase 4: Finishing'];
    if (!in_array($phase, $valid_phases)) {
        throw new Exception('Invalid phase');
    }

    // Validate supplier name
    if (strlen($supplier_name) < 3) {
        throw new Exception('Supplier name must be at least 3 characters');
    }

    // Validate total amount
    if ($total_amount <= 0) {
        throw new Exception('Total amount must be greater than zero');
    }

    // Validate status
    $valid_statuses = ['PENDING', 'APPROVED'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid status');
    }

    // Validate line items
    $valid_categories = ['MATERIALS', 'EQUIPMENT', 'PROFESSIONAL_FEES', 'SUBCONTRACTOR', 'OVERHEAD'];
    $calculated_total = 0;
    
    foreach ($line_items as $item) {
        if (!isset($item['category']) || !isset($item['description']) || !isset($item['quantity']) || !isset($item['unitCost'])) {
            throw new Exception('Invalid line item data');
        }
        
        if (!in_array($item['category'], $valid_categories)) {
            throw new Exception('Invalid category in line item');
        }
        
        if (strlen($item['description']) < 10) {
            throw new Exception('Line item description must be at least 10 characters');
        }
        
        if ($item['quantity'] <= 0 || $item['unitCost'] <= 0) {
            throw new Exception('Line item quantity and unit cost must be greater than zero');
        }
        
        $calculated_total += $item['subtotal'];
    }
    
    // Verify total amount matches calculated total
    if (abs($calculated_total - $total_amount) > 0.01) {
        throw new Exception('Total amount does not match line items sum');
    }

    $stmt = $conn->prepare("SELECT project_id FROM projects WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Project not found');
    }
    $stmt->close();

    // Handle file upload if present
    $receipt_path = null;
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error occurred');
        }

        $file = $_FILES['receipt'];
        
        // Validate file type
        $allowed_types = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);

        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('Invalid file type. Only PDF, PNG, and JPG are allowed');
        }

        // Validate file size (5MB max)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $max_size) {
            throw new Exception('File size exceeds 5MB limit');
        }

        // Create uploads directory if it doesn't exist
        $upload_dir = __DIR__ . '/uploads/receipts/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_filename = 'receipt_' . time() . '_' . uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $unique_filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('Failed to save uploaded file');
        }

        $receipt_path = 'uploads/receipts/' . $unique_filename;
    }

    // Check if supplier exists, if not create one
    $supplier_id = null;
    $stmt = $conn->prepare("SELECT supplier_id FROM budget_suppliers WHERE name = ? LIMIT 1");
    $stmt->bind_param("s", $supplier_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $supplier_id = $result->fetch_assoc()['supplier_id'];
    } else {
        // Create new supplier
        $stmt_insert = $conn->prepare("INSERT INTO budget_suppliers (name, status) VALUES (?, 'ACTIVE')");
        $stmt_insert->bind_param("s", $supplier_name);
        if (!$stmt_insert->execute()) {
            throw new Exception('Failed to create supplier record');
        }
        $supplier_id = $stmt_insert->insert_id;
        $stmt_insert->close();
    }
    $stmt->close();

    // Start transaction
    $conn->begin_transaction();

    try {
        // FIX: Disable Foreign Key Checks temporarily
        // This allows us to insert a project_id from Main DB into Budget DB
        $conn->query("SET FOREIGN_KEY_CHECKS=0");

        // Insert each line item as a separate expense record
        $sql = "INSERT INTO budget_expenses (project_id, supplier_id, phase, expense_date, category, description, quantity, unit_cost, amount, receipt_path, status, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $expense_ids = [];
        
        foreach ($line_items as $index => $item) {
            // ... [Binding logic remains the same] ...
            $category = $item['category'];
            $description = $item['description'];
            $quantity = $item['quantity'];
            $unit_cost = $item['unitCost'];
            $subtotal = $item['subtotal'];
            
            $item_receipt_path = ($index === 0) ? $receipt_path : null;
            
            $stmt->bind_param("iissssddssss", 
                $project_id, 
                $supplier_id, 
                $phase,
                $expense_date, 
                $category, 
                $description, 
                $quantity,
                $unit_cost,
                $subtotal, 
                $item_receipt_path, 
                $status, 
                $notes
            );

            if (!$stmt->execute()) {
                throw new Exception('Failed to save expense line item: ' . $stmt->error);
            }
            
            $expense_ids[] = $stmt->insert_id;
        }
        
        $stmt->close();
        
        // FIX: Re-enable Foreign Key Checks
        $conn->query("SET FOREIGN_KEY_CHECKS=1");

        // Commit transaction
        $conn->commit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        // Ensure checks are back on
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        
        // ... [File cleanup logic remains the same] ...
        if ($receipt_path && file_exists(__DIR__ . '/' . $receipt_path)) {
            unlink(__DIR__ . '/' . $receipt_path);
        }
        
        throw $e;
    }

    // Get updated totals
    $stmt = $conn->prepare("SELECT 
        COALESCE(SUM(amount), 0) as total_spending,
        COUNT(*) as total_expenses
        FROM budget_expenses 
        WHERE project_id = ? AND status = 'APPROVED'");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $totals = $result->fetch_assoc();
    $stmt->close();

    // Success response
    echo json_encode([
        'success' => true,
        'message' => count($line_items) > 1 ? count($line_items) . ' expense items added successfully' : 'Expense added successfully',
        'expense_ids' => $expense_ids,
        'line_items_count' => count($line_items),
        'totals' => [
            'total_spending' => $totals['total_spending'],
            'total_expenses' => $totals['total_expenses']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
