<?php
// modules/procurement/php/fetch_stockin.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
require_once __DIR__ . '/../../../core/ProjectContext.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$project_id = $_GET['project_id'] ?? ProjectContext::getProjectId();

$params = "?project_id=$project_id";
$res = ApiHelper::call("procurement/stockin$params", $method);

$rows = $res['data']['data'] ?? [];
http_response_code($res['status']);
echo json_encode($rows);
?>