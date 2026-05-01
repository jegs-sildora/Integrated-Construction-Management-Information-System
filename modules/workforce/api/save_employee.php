<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Special: Bridge to POST workforce/employees
$method = 'POST';
$params = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';

// Handle both form data and JSON body
$data = $_POST;
$rawInput = file_get_contents('php://input');
$decoded = json_decode($rawInput, true);
if (is_array($decoded)) {
    $data = array_merge($data, $decoded);
}

$res = ApiHelper::call('workforce/employees' . $params, $method, $data);

http_response_code($res['status']);
echo json_encode($res['data']);
?>