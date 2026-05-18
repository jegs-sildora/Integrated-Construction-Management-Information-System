<?php
// modules/procurement/php/get_approved_pos.php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
require_once __DIR__ . '/../../../core/ProjectContext.php';

// Get project_id from GET or centralized context
$project_id = $_GET['project_id'] ?? ProjectContext::getProjectId();

if ($project_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Project ID']);
    exit;
}

// Fetch Approved POs via Microservice
$res = ApiHelper::get("procurement/orders?project_id=$project_id&status=APPROVED");
$pos = [];
if ($res['status'] === 200 && isset($res['data']['orders'])) {
    $pos = $res['data']['orders'];
}

echo json_encode(['success' => true, 'pos' => $pos]);
?>