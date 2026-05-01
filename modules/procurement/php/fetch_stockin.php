<?php
// modules/procurement/php/fetch_stockin.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

$params = "?project_id=$project_id";
$res = ApiHelper::call("procurement/stockin$params", $method);

http_response_code($res['status']);
echo json_encode($res['data']);
?>