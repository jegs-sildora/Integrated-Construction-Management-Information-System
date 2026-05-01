<?php
// modules/procurement/php/fetch_inventory.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$phase_id = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;

$params = "?project_id=$project_id&phase_id=$phase_id";
$res = ApiHelper::call("procurement/inventory$params", $method);

http_response_code($res['status']);
echo json_encode($res['data']);
?>