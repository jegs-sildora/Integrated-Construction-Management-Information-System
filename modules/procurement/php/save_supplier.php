<?php
// modules/procurement/php/save_supplier.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$res = ApiHelper::call("procurement/suppliers", $method, $_POST);

http_response_code($res['status']);
echo json_encode($res['data']);
?>