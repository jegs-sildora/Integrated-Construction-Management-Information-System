<?php
/**
 * get_project_phases.php - Bridge to Budget Service
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

require_once __DIR__ . '/../../../core/ProjectContext.php';

header('Content-Type: application/json');

$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : ProjectContext::getProjectId();

// Call Project Service via Gateway to get phases
$res = ApiHelper::get('project/phases?project_id=' . $project_id);

http_response_code($res['status']);
echo json_encode($res['data']);
?>
