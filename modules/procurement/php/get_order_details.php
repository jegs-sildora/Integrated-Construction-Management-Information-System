<?php
// modules/procurement/php/get_order_details.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$po_id = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;

$res = ApiHelper::call("procurement/orders/$po_id/details", $method);

http_response_code($res['status']);
echo json_encode($res['data']);
?>