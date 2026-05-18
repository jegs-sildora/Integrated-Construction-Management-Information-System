<?php
// modules/procurement/php/fetch_inventory.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
require_once __DIR__ . '/../../../core/ProjectContext.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$project_id = $_GET['project_id'] ?? ProjectContext::getProjectId();
$phase_id = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;

$params = "?project_id=$project_id&phase_id=$phase_id";
$res = ApiHelper::call("procurement/inventory$params", $method);

$inventory = $res['data']['inventory'] ?? [];
http_response_code($res['status']);
echo json_encode($inventory);
?>