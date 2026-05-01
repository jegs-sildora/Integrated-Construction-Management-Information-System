<?php
/**
 * get_project_phases.php - Bridge to Budget Service
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

header('Content-Type: application/json');

$project_id = $_GET['project_id'] ?? 0;

// Call Budget Service via Gateway
$res = ApiHelper::get('budget/phases?project_id=' . $project_id);

http_response_code($res['status']);
echo json_encode($res['data']);
?>
