<?php
// modules/procurement/php/save_supplier.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

$payload = [
	'supplier_id' => isset($_POST['id']) ? intval($_POST['id']) : null,
	'supplier_name' => $_POST['name'] ?? $_POST['supplier_name'] ?? '',
	'contact_person' => $_POST['person'] ?? $_POST['contact_person'] ?? '',
	'contact_number' => $_POST['phone'] ?? $_POST['contact_number'] ?? '',
	'email' => $_POST['email'] ?? '',
	'address' => $_POST['address'] ?? '',
	'status' => $_POST['status'] ?? 'Active'
];

$res = ApiHelper::call("procurement/suppliers", $method, $payload);

$success = (bool)($res['data']['success'] ?? false);
http_response_code($res['status']);
echo json_encode([
	'status' => $success ? 'success' : 'error',
	'message' => $res['data']['message'] ?? ($success ? 'Success' : 'Failed'),
	'id' => $res['data']['id'] ?? null
]);
?>