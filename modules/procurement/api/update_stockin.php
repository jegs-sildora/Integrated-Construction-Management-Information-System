<?php
// modules/procurement/php/update_stockin.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$stock_id = 0;
if ($data && isset($data['stock_in_id'])) {
    $stock_id = intval($data['stock_in_id']);
}

$res = ApiHelper::call("procurement/stockin/$stock_id", 'PUT', $data ? $data : $_POST);

http_response_code($res['status']);
echo json_encode($res['data']);
?>