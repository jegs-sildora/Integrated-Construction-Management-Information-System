<?php
// ============================================================
// DEPRECATED: Manual expense updating has been removed.
// Expenses are now automatically synced from the Procurement module.
// To modify expense data, update the corresponding Purchase Order.
// ============================================================

header('Content-Type: application/json');

echo json_encode([
    'success' => false,
    'message' => 'Manual expense editing has been disabled. Expenses are now automatically synced from completed Purchase Orders in the Procurement module. To modify expense data, please update the corresponding Purchase Order.',
    'deprecated' => true
]);
exit;
?>
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
    $stmt = $conn->prepare("SELECT project_id FROM icmis_projects WHERE project_id = ?");
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
    $stmt = $conn->prepare("SELECT supplier_id FROM procurement_suppliers WHERE supplier_name = ?");
    $stmt->bind_param("s", $supplier_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $supplier = $result->fetch_assoc();
        $supplier_id = $supplier['supplier_id'];
    } else {
        // Create new supplier
        $stmt_insert = $conn->prepare("INSERT INTO procurement_suppliers (supplier_name, status) VALUES (?, 'Active')");
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
        // Look up phase_id from phase name
        $phase_id = null;
        $stmt_phase = $conn->prepare("SELECT phase_id FROM icmis_project_phases WHERE project_id = ? AND phase_name = ?");
        $stmt_phase->bind_param("is", $project_id, $phase);
        $stmt_phase->execute();
        $result_phase = $stmt_phase->get_result();
        if ($row_phase = $result_phase->fetch_assoc()) {
            $phase_id = $row_phase['phase_id'];
        }
        $stmt_phase->close();

        // Update the main expense record with the first line item (matching new schema)
        $first_item = $line_items[0];
        
        $sql_update = "UPDATE budget_expenses SET 
                       project_id = ?, 
                       phase_id = ?,
                       expense_date = ?, 
                       category = ?, 
                       description = ?, 
                       supplier_id = ?, 
                       amount = ?, 
                       status = ?
                       WHERE expense_id = ?";
        
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param(
            "iisssidsi",
            $project_id,
            $phase_id,
            $expense_date,
            $first_item['category'],
            $first_item['description'],
            $supplier_id,
            $first_item['subtotal'],
            $status,
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
                project_id, phase_id, expense_date, category, description, 
                supplier_id, amount, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $created_by = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param(
                "iisssidsi",
                $project_id,
                $phase_id,
                $expense_date,
                $item['category'],
                $item['description'],
                $supplier_id,
                $item['subtotal'],
                $status,
                $created_by
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
