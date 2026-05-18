<?php
/**
 * Get Phase Expenses - Now sourced from Microservices via Gateway
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

require_once __DIR__ . '/../../../core/ProjectContext.php';

try {
    // Validate inputs
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

    // Fetch expenses from Budget service via Gateway
    $api_params = [
        'project_id' => $project_id,
        'status' => 'APPROVED'
    ];
    if ($phase_id > 0) {
        $api_params['phase_id'] = $phase_id;
    }

    $response = ApiHelper::get('budget/expenses?' . http_build_query($api_params));

    if ($response['status'] !== 200) {
        throw new Exception('API Error: ' . ($response['data']['error'] ?? 'Unknown error'));
    }

    $expenses = $response['data']['expenses'] ?? [];

    // Enrich supplier names via Procurement Service
    $supplierMap = [];
    $supplierIds = array_values(array_unique(array_filter(array_map(function ($exp) {
        return $exp['supplier_id'] ?? null;
    }, $expenses))));

    if (!empty($supplierIds)) {
        $supRes = ApiHelper::get('procurement/suppliers?per_page=1000');
        if ($supRes['status'] === 200) {
            $suppliers = $supRes['data']['suppliers'] ?? [];
            foreach ($suppliers as $sup) {
                $supplierMap[$sup['supplier_id']] = $sup['supplier_name'];
            }
        }
    }

    foreach ($expenses as &$expense) {
        $supplier_id = $expense['supplier_id'] ?? null;
        $expense['supplier_name'] = $supplierMap[$supplier_id] ?? 'N/A';
    }

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
