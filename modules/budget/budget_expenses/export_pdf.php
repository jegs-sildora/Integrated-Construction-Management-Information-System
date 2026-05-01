<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : (isset($_GET['project_id']) ? intval($_GET['project_id']) : 0);
$report_type = isset($_POST['report_type']) ? $_POST['report_type'] : (isset($_GET['report_type']) ? $_GET['report_type'] : 'expense-log');
$phase = isset($_POST['phase']) ? $_POST['phase'] : (isset($_GET['phase']) ? $_GET['phase'] : '');

if ($project_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Project ID is required']);
    exit;
}

// Fetch Data from API
$api_params = [
    'project_id' => $project_id,
    'report_type' => $report_type,
    'phase' => $phase
];

$response = ApiHelper::get('budget/reports/export?' . http_build_query($api_params));

if ($response['status'] !== 200) {
    echo json_encode(['success' => false, 'message' => 'API Error: ' . ($response['data']['error'] ?? 'Unknown error')]);
    exit;
}

echo json_encode(['success' => true, 'data' => $response['data']]);
?>
