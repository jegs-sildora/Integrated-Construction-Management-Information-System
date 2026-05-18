<?php
// modules/procurement/php/get_inventory_items.php
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

// Fetch Inventory via Microservice
$res = ApiHelper::get("procurement/inventory?project_id=$project_id");
$items = [];
if ($res['status'] === 200 && isset($res['data']['inventory'])) {
    $items = $res['data']['inventory'];
}

echo json_encode(['success' => true, 'items' => $items]);
?>