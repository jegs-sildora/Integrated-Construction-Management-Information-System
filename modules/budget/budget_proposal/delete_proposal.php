<?php
/**
 * delete_proposal.php - Bridge to Budget Service
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['proposal_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing proposal_id']);
    exit;
}

// Call Budget Service via Gateway using RESTful DELETE pattern
$res = ApiHelper::call('budget/proposals/' . $data['proposal_id'], 'DELETE');

http_response_code($res['status']);
echo json_encode($res['data']);
?>
