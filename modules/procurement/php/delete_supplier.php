<?php
// modules/procurement/php/delete_supplier.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

$res = ApiHelper::call("procurement/suppliers/$id", 'DELETE');

$success = (bool)($res['data']['success'] ?? false);
http_response_code($res['status']);
echo json_encode([
	'status' => $success ? 'success' : 'error',
	'message' => $res['data']['message'] ?? ($success ? 'Deleted' : 'Delete failed')
]);
?>