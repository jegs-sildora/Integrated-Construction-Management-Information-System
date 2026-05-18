<?php
// modules/procurement/php/update_supplier.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

$payload = [
	'supplier_id' => $id,
	'supplier_name' => $_POST['name'] ?? $_POST['supplier_name'] ?? '',
	'contact_person' => $_POST['person'] ?? $_POST['contact_person'] ?? '',
	'contact_number' => $_POST['phone'] ?? $_POST['contact_number'] ?? '',
	'email' => $_POST['email'] ?? '',
	'address' => $_POST['address'] ?? '',
	'status' => $_POST['status'] ?? 'Active'
];

$res = ApiHelper::call("procurement/suppliers/$id", 'PUT', $payload);

$success = (bool)($res['data']['success'] ?? false);
http_response_code($res['status']);
echo json_encode([
	'status' => $success ? 'success' : 'error',
	'message' => $res['data']['message'] ?? ($success ? 'Updated' : 'Update failed')
]);
?>