<?php
/**
 * Get Phase Expenses - Now sourced from Microservices via Gateway
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

try {
    // Validate inputs
    if (!isset($_GET['project_id']) || !isset($_GET['phase'])) {
        throw new Exception('Missing required parameters');
    }

    $project_id = intval($_GET['project_id']);
    $phase = $_GET['phase'];

    // Fetch expenses from Budget service via Gateway
    $api_params = [
        'project_id' => $project_id,
        'phase' => $phase,
        'status' => 'APPROVED'
    ];
    
    $response = ApiHelper::get('budget/expenses?' . http_build_query($api_params));

    if ($response['status'] !== 200) {
        throw new Exception('API Error: ' . ($response['data']['error'] ?? 'Unknown error'));
    }

    $expenses = $response['data']['expenses'] ?? [];

    echo json_encode([
        'success' => true,
        'expenses' => $expenses,
        'count' => count($expenses),
        'source' => 'budget_service'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
