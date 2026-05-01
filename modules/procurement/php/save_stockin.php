<?php
// modules/procurement/php/save_stockin.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($method === 'POST' && $data) {
    $res = ApiHelper::call("procurement/stockin", $method, $data);
} else {
    $res = ApiHelper::call("procurement/stockin", $method, $_POST);
}

http_response_code($res['status']);
echo json_encode($res['data']);
?>