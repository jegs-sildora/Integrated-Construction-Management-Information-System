<?php
/**
 * ========================= API Bridge: Projects =========================
 * Purpose: Proxies requests to the Project Microservice via API Gateway.
 * Maintains compatibility with existing frontend JS.
 * ============================================================================ 
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$params = $_GET;
// For file uploads or complex forms, we'd need more logic, 
// but for standard ICMIS forms, $_POST is fine.
$data = $_POST; 

// Handle JSON input if sent from JS fetch (though existing JS uses FormData)
if ($method === 'POST' && empty($data)) {
    $json = json_decode(file_get_contents('php://input'), true);
    if ($json) $data = $json;
}

// 1. Determine Endpoint
$endpoint = 'project/projects';
if (!empty($params)) {
    $endpoint .= '?' . http_build_query($params);
}

// 2. Call Microservice via Gateway
$res = ApiHelper::call($endpoint, $method, $data);

// 3. Data Enrichment (Cross-Service Mapping)
// Since microservices are isolated, we must map Workforce names here.
if ($res['status'] === 200 && is_array($res['data'])) {
    
    // Enrich ALL projects list
    if (isset($res['data']['projects']) && is_array($res['data']['projects'])) {
        $empRes = ApiHelper::get('workforce/employees?action=list');
        $employees = $empRes['data']['data'] ?? [];
        $map = [];
        foreach ($employees as $emp) {
            $map[$emp['employee_id']] = $emp['first_name'] . ' ' . $emp['last_name'];
        }
        foreach ($res['data']['projects'] as &$proj) {
            $proj['manager_name'] = $map[$proj['project_manager_id'] ?? 0] ?? 'N/A';
        }
    }

    // Enrich SINGLE project fetch
    if (isset($res['data']['project']) && is_array($res['data']['project'])) {
        $proj = &$res['data']['project'];
        if (!empty($proj['project_manager_id'])) {
            $empRes = ApiHelper::get('workforce/employees?action=get&id=' . $proj['project_manager_id']);
            $emp = $empRes['data']['data'] ?? null;
            $proj['manager_name'] = $emp ? $emp['first_name'] . ' ' . $emp['last_name'] : 'N/A';
        }
    }
}

// 4. Return Response
http_response_code($res['status']);
echo json_encode($res['data']);
