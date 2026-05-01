<?php
// modules/procurement/php/fetch_suppliers.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? $_GET['per_page'] : 10;

$params = "?search=" . urlencode($search) . "&page=$page&per_page=$per_page";
$res = ApiHelper::call("procurement/suppliers$params", $method);

http_response_code($res['status']);
echo json_encode($res['data']);
?>