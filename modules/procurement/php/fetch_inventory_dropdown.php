<?php
/**
 * API Bridge: Inventory Dropdown
 * Proxies request to Procurement Service via Gateway
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

// Determine project_id from GET or session
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : (isset($_SESSION['current_project_id']) ? intval($_SESSION['current_project_id']) : 0);

$endpoint = 'procurement/inventory?action=dropdown';
if ($project_id > 0) {
    $endpoint .= '&project_id=' . $project_id;
}

$res = ApiHelper::get($endpoint);

if ($res['status'] === 200) {
    echo json_encode($res['data']['inventory'] ?? []);
} else {
    echo json_encode([]);
}
?>