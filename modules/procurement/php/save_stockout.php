<?php
// modules/procurement/php/save_stockout.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$res = ApiHelper::call("procurement/stockout", $method, $_POST);

http_response_code($res['status']);
echo json_encode($res['data']);
?>