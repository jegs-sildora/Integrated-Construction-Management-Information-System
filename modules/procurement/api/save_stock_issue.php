<?php
// modules/procurement/php/save_stock_issue.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

$payload = [
	'item_id' => isset($_POST['stock_itemID']) ? intval($_POST['stock_itemID']) : (isset($_POST['item_id']) ? intval($_POST['item_id']) : 0),
	'quantity' => $_POST['stock_quantity'] ?? $_POST['quantity'] ?? 0,
	'project_id' => $_POST['stock_projectID'] ?? $_POST['project_id'] ?? 0,
	'issued_to_employee_id' => $_POST['stock_issuedTo_id'] ?? $_POST['issued_to_employee_id'] ?? null,
	'date_issued' => $_POST['date_issued'] ?? null
];

$res = ApiHelper::call("procurement/stockout", $method, $payload);

$success = (bool)($res['data']['success'] ?? false);
http_response_code($res['status']);
echo json_encode([
	'status' => $success ? 'success' : 'error',
	'message' => $res['data']['message'] ?? ($success ? 'Stock out successful' : 'Stock out failed')
]);
?>