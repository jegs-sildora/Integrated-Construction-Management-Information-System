<?php
/**
 * update_proposal.php - Bridge to Budget Service
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

// Call Budget Service via Gateway
$res = ApiHelper::call('budget/proposals', $method, $data);

http_response_code($res['status']);
echo json_encode($res['data']);
?>
