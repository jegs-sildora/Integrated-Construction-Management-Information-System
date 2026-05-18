<?php
/**
 * get_expense_details.php - Bridge to Budget Service
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

header('Content-Type: application/json');

$expense_id = $_GET['id'] ?? 0;

// Call Budget Service via Gateway
$res = ApiHelper::get('budget/expenses?fetch_id=' . $expense_id);

http_response_code($res['status']);
echo json_encode($res['data']);
?>
