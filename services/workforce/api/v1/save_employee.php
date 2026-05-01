<?php
/**
 * Save Employee API v1 - Workforce Service
 */
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../../Database.php';

$db = Database::getConnection();

// Handle JSON Input from API Gateway
if (empty($_POST)) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (is_array($input)) {
        $_POST = $input;
        $_REQUEST = array_merge($_REQUEST, $input);
    }
}

// Basic validation
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

if (!in_array($status, ['Active', 'Inactive', 'Terminated'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid status value.']);
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
    // Using a path relative to the service
    $targetDir = __DIR__ . '/../../assets/avatars';
    if(!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    $fileName = 'avatar_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = $targetDir . '/' . $fileName;
    if(!move_uploaded_file($f['tmp_name'], $dest)){
        http_response_code(500);
        echo json_encode(['success'=>false,'error'=>'Failed to move uploaded file']);
        exit;
    }
    $avatar_path = 'services/workforce/assets/avatars/' . $fileName;
}

try{
    $stmt = $db->prepare("INSERT INTO employees (employee_code, user_id, job_title_id, first_name, last_name, email, phone, status, hire_date) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?)");
    
    $ok = $stmt->execute([$employee_code, $job_title_id, $first_name, $last_name, $email, $phone, $status, $hire_date]);
    
    if(!$ok){
        http_response_code(500);
        echo json_encode(['success'=>false,'error'=>'Failed to save employee']);
        exit;
    }
    $newId = $db->lastInsertId();

    echo json_encode(['success'=>true,'employee_id'=>$newId, 'avatar_path' => $avatar_path]);
} catch(Exception $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
