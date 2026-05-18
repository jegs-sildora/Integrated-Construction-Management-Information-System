<?php
// modules/procurement/php/save_order.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$res = ApiHelper::call("procurement/orders", $method, $_POST);

http_response_code($res['status']);
echo json_encode($res['data']);
?>