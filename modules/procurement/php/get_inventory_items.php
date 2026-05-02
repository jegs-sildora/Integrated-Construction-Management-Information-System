<?php
// modules/procurement/php/get_inventory_items.php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

// Get project_id from GET or session
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : (isset($_SESSION['current_project_id']) ? intval($_SESSION['current_project_id']) : 0);

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