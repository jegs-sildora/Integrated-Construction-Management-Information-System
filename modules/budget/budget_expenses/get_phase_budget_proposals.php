<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

require_once __DIR__ . '/../../../core/ProjectContext.php';

try {
    $project_id = ProjectContext::getProjectId();
    $phase = $_GET['phase'] ?? '';

    if ($project_id <= 0 || empty($phase)) {
        throw new Exception('Missing required parameters (project_id or phase)');
    }

    // Resolve phase name to phase_id via Project Service
    $phase_id = 0;
    $phaseRes = ApiHelper::get('project/phases?project_id=' . $project_id);
    if ($phaseRes['status'] === 200) {
        foreach (($phaseRes['data']['phases'] ?? []) as $ph) {
            if (($ph['phase_name'] ?? '') === $phase) {
                $phase_id = intval($ph['phase_id']);
                break;
            }
        }
    }

    // Call Budget Service via Gateway
    $api_params = [
        'project_id' => $project_id,
        'status' => 'APPROVED'
    ];
    if ($phase_id > 0) {
        $api_params['phase_id'] = $phase_id;
    }

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
