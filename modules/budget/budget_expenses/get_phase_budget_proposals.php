<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

try {
    if (!isset($_GET['project_id']) || !isset($_GET['phase'])) {
        throw new Exception('Missing required parameters');
    }

    $project_id = intval($_GET['project_id']);
    $phase = $_GET['phase'];

    // Call Budget Service via Gateway
    $api_params = [
        'project_id' => $project_id,
        'phase' => $phase,
        'status' => 'APPROVED'
    ];
    
    $response = ApiHelper::get('budget/proposals?' . http_build_query($api_params));

    if ($response['status'] !== 200) {
        throw new Exception('API Error: ' . ($response['data']['error'] ?? 'Unknown error'));
    }

    echo json_encode([
        'success' => true,
        'proposals' => $response['data']['proposals'] ?? [],
        'count' => count($response['data']['proposals'] ?? [])
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
