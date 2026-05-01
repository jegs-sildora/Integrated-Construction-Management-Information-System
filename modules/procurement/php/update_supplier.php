<?php
// modules/procurement/php/update_supplier.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

$res = ApiHelper::call("procurement/suppliers/$id", 'PUT', $_POST);

http_response_code($res['status']);
echo json_encode($res['data']);
?>