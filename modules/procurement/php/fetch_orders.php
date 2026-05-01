<?php
// modules/procurement/php/fetch_orders.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

$params = "?project_id=$project_id";
$res = ApiHelper::call("procurement/orders$params", $method);

http_response_code($res['status']);
echo json_encode($res['data']);
?>