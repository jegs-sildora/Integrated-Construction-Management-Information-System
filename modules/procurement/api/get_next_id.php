<?php
// modules/procurement/php/get_next_id.php
// Returns the next PO reference number via microservice

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

$res = ApiHelper::get("procurement/orders");
$next_id = 1;

if ($res['status'] === 200 && isset($res['data']['orders'])) {
    $year = date('Y');
    $count = 0;
    foreach ($res['data']['orders'] as $order) {
        if (strpos($order['po_reference'], "PO-$year") === 0) {
            $count++;
        }
    }
    $next_id = $count + 1;
}

$po_reference = sprintf("PO-%s-%04d", date('Y'), $next_id);

echo json_encode(['success' => true, 'next_id' => $po_reference]);
?>