<?php
// modules/procurement/purchase_order/delete_order.php

if (session_status() === PHP_SESSION_NONE) session_start();
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
require_once __DIR__ . '/../../../core/Logger.php';

$po_id = isset($_POST['po_id']) ? intval($_POST['po_id']) : 0;

if ($po_id <= 0) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID']);
    exit;
}

try {
    // 1. Fetch info for logging
    $info = ApiHelper::get("procurement/orders?id=$po_id");
    $ref = "PO#$po_id";
    if ($info['status'] === 200 && isset($info['data']['order'])) {
        $ref = $info['data']['order']['po_reference'];
    }

    // 2. Request deletion from microservice
    $response = ApiHelper::post('procurement/orders', [
        'action' => 'delete',
        'po_id' => $po_id
    ]);

    if ($response['status'] !== 200 || !($response['data']['success'] ?? false)) {
        throw new Exception($response['data']['message'] ?? 'Failed to delete order via API');
    }

    // 3. Log the audit trail
    Logger::log('DELETE', 'Procurement', "Purchase Order Deleted: $ref", $po_id);

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Order deleted successfully',
        'po_reference' => $ref
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
