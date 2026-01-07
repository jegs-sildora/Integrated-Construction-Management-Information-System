<?php
/**
 * Save Employee API - Workforce Module
 * Handles creating a new employee
 * 
 * DATABASE SCHEMA (from icmis_db.sql):
 * workforce_employees: employee_id, employee_code, user_id, job_title_id, first_name, last_name, email, phone, status (ENUM: Active, Inactive, Terminated), hire_date
 */
require_once __DIR__ . '/../../../../config/database.php';
header('Content-Type: application/json');

// Basic server-side validation
$required = ['first_name','last_name','email','employee_code'];
foreach($required as $r){
    if(empty($_POST[$r])){
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "$r is required"]);
        exit;
    }
}

$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$email = $_POST['email'];
$employee_code = $_POST['employee_code'];
$phone = $_POST['phone'] ?? null;
$job_title_id = !empty($_POST['job_title_id']) ? intval($_POST['job_title_id']) : null;
$status = $_POST['status'] ?? 'Active';
$hire_date = !empty($_POST['hire_date']) ? $_POST['hire_date'] : null;

// Validate status value matches DB ENUM
if (!in_array($status, ['Active', 'Inactive', 'Terminated'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid status value. Must be Active, Inactive, or Terminated']);
    exit;
}

// handle photo upload
$avatar_path = null;
if(!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK){
    $f = $_FILES['photo'];
    $maxSize = 2 * 1024 * 1024;
    if($f['size'] > $maxSize){
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'Photo too large (max 2MB)']);
        exit;
    }
    $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg','jpeg','png','gif'];
    if(!in_array(strtolower($ext), $allowed)){
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'Invalid photo type']);
        exit;
    }
    $targetDir = __DIR__ . '/../../../assets/images/avatars';
    if(!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    $fileName = 'avatar_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = $targetDir . '/' . $fileName;
    if(!move_uploaded_file($f['tmp_name'], $dest)){
        http_response_code(500);
        echo json_encode(['success'=>false,'error'=>'Failed to move uploaded file']);
        exit;
    }
    $avatar_path = 'assets/images/avatars/' . $fileName;
}

// insert into workforce_employees
try{
    $stmt = $conn->prepare("INSERT INTO workforce_employees (employee_code, user_id, job_title_id, first_name, last_name, email, phone, status, hire_date) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?)");
    // Types: s=employee_code, i=job_title_id, s=first_name, s=last_name, s=email, s=phone, s=status, s=hire_date
    $stmt->bind_param('sissssss', $employee_code, $job_title_id, $first_name, $last_name, $email, $phone, $status, $hire_date);
    
    $ok = $stmt->execute();
    if(!$ok){
        http_response_code(500);
        echo json_encode(['success'=>false,'error'=>$stmt->error]);
        exit;
    }
    $newId = $conn->insert_id;
    $stmt->close();

    echo json_encode(['success'=>true,'employee_id'=>$newId]);
} catch(Exception $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
