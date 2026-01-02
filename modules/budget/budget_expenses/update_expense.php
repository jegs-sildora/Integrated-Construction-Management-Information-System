<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');
include __DIR__ . '/../connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // Verify request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate expense_id
    if (!isset($_POST['expense_id']) || empty($_POST['expense_id'])) {
        throw new Exception('Expense ID is required');
    }

    $expense_id = intval($_POST['expense_id']);

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

    // Check if date is not in the future
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

    // Validate line items (allow same categories as add_expense)
    $valid_categories = ['MATERIALS', 'LABOR', 'EQUIPMENT', 'PROFESSIONAL_FEES', 'SUBCONTRACTOR', 'OVERHEAD'];
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

    // Verify expense exists
    $stmt = $conn->prepare("SELECT expense_id, project_id, receipt_path FROM budget_expenses WHERE expense_id = ?");
    $stmt->bind_param("i", $expense_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Expense not found');
    }
    $existing_expense = $result->fetch_assoc();
    $old_receipt_path = $existing_expense['receipt_path'];
    $stmt->close();

    // Verify project exists
    $stmt = $conn->prepare("SELECT project_id FROM projects WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Project not found');
    }
    $stmt->close();

    // Handle file upload if present
    $receipt_path = $old_receipt_path; // Keep old path by default
    $new_file_uploaded = false;

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
        $upload_dir = __DIR__ . '/../uploads/receipts/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_filename = 'receipt_' . time() . '_' . uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $unique_filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('Failed to move uploaded file');
        }

        $receipt_path = 'uploads/receipts/' . $unique_filename;
        $new_file_uploaded = true;
    }

    // Check/create supplier
    $stmt = $conn->prepare("SELECT supplier_id FROM budget_suppliers WHERE name = ?");
    $stmt->bind_param("s", $supplier_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $supplier = $result->fetch_assoc();
        $supplier_id = $supplier['supplier_id'];
    } else {
        // Create new supplier
        $stmt_insert = $conn->prepare("INSERT INTO budget_suppliers (name, created_at) VALUES (?, NOW())");
        $stmt_insert->bind_param("s", $supplier_name);
        
        if (!$stmt_insert->execute()) {
            throw new Exception('Failed to create supplier');
        }
        
        $supplier_id = $conn->insert_id;
        $stmt_insert->close();
    }
    $stmt->close();

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Update the main expense record with the first line item
        $first_item = $line_items[0];
        
        $sql_update = "UPDATE budget_expenses SET 
                       project_id = ?, 
                       expense_date = ?, 
                       phase = ?, 
                       category = ?, 
                       description = ?, 
                       supplier_id = ?, 
                       quantity = ?,
                       unit_cost = ?,
                       amount = ?, 
                       status = ?, 
                       notes = ?, 
                       receipt_path = ?
                       WHERE expense_id = ?";
        
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param(
            "issssidddsssi",
            $project_id,
            $expense_date,
            $phase,
            $first_item['category'],
            $first_item['description'],
            $supplier_id,
            $first_item['quantity'],
            $first_item['unitCost'],
            $first_item['subtotal'],
            $status,
            $notes,
            $receipt_path,
            $expense_id
        );

        if (!$stmt_update->execute()) {
            throw new Exception('Failed to update expense: ' . $stmt_update->error);
        }
        $stmt_update->close();

        // If there are additional line items, insert them as new expense records
        $additional_expense_ids = [];
        
        for ($i = 1; $i < count($line_items); $i++) {
            $item = $line_items[$i];
            
            $sql_insert = "INSERT INTO budget_expenses (
                project_id, expense_date, phase, category, description, 
                supplier_id, quantity, unit_cost, amount, status, notes, receipt_path
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $null_receipt_path = null;
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param(
                "issssidddsss",
                $project_id,
                $expense_date,
                $phase,
                $item['category'],
                $item['description'],
                $supplier_id,
                $item['quantity'],
                $item['unitCost'],
                $item['subtotal'],
                $status,
                $notes,
                $null_receipt_path
            );

            if (!$stmt_insert->execute()) {
                throw new Exception('Failed to insert additional line item: ' . $stmt_insert->error);
            }
            
            $additional_expense_ids[] = $conn->insert_id;
            $stmt_insert->close();
        }

        // Commit transaction
        $conn->commit();

        // Delete old receipt file if new one was uploaded
        if ($new_file_uploaded && $old_receipt_path && file_exists(__DIR__ . '/../' . $old_receipt_path)) {
            @unlink(__DIR__ . '/../' . $old_receipt_path);
        }

        // Success response
        $response = [
            'success' => true,
            'message' => 'Expense updated successfully',
            'expense_id' => $expense_id,
            'additional_expense_ids' => $additional_expense_ids,
            'line_items_count' => count($line_items),
            'supplier_id' => $supplier_id
        ];

        if ($receipt_path) {
            $response['receipt_path'] = $receipt_path;
        }

        echo json_encode($response);

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        
        // Delete uploaded file if transaction failed
        if ($new_file_uploaded && isset($upload_path) && file_exists($upload_path)) {
            @unlink($upload_path);
        }
        
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
