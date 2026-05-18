<?php
// modules/procurement/php/get_po_items.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$po_id = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;

$res = ApiHelper::call("procurement/orders?id=$po_id", $method);

if ($res['status'] !== 200 || !($res['data']['success'] ?? false)) {
	http_response_code($res['status']);
	echo json_encode(['success' => false, 'items' => []]);
	exit;
}

$order = $res['data']['order'] ?? [];
$items = $order['items'] ?? [];

$normalized = [];
foreach ($items as $item) {
	$normalized[] = [
		'id' => $item['po_item_id'] ?? $item['id'] ?? null,
		'item_name' => $item['item_name'] ?? '',
		'quantity' => $item['quantity'] ?? 0,
		'unit' => $item['unit'] ?? 'pcs'
	];
}

http_response_code($res['status']);
echo json_encode([
	'success' => true,
	'items' => $normalized
]);
?>