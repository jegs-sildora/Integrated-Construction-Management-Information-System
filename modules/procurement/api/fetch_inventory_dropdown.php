<?php
/**
 * API Bridge: Inventory Dropdown
 * Proxies request to Procurement Service via Gateway
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
require_once __DIR__ . '/../../../core/ProjectContext.php';

// Determine project_id using centralized context
$project_id = $_GET['project_id'] ?? ProjectContext::getProjectId();

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