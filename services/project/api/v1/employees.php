<?php
/**
 * ========================= API: Employees =========================
 * Purpose: Proxy to fetch employees available for project tasks from 
 * the Workforce microservice.
 * ============================================================================ 
 */

header('Content-Type: application/json');

// Get Workforce Service URL from environment or default to internal Docker network
$workforce_service_url = getenv('WORKFORCE_SERVICE_URL') ?: 'http://workforce-service';

// Use internal service API to fetch employee list
$url = $workforce_service_url . '/api/v1/employees.php?action=list';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

// Forward Authorization header if present
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . $_SERVER['HTTP_AUTHORIZATION']]);
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['success']) && $data['success'] && isset($data['data'])) {
        // Transform data to match legacy modules/project/api/employees.php format
        $employees = [];
        foreach ($data['data'] as $emp) {
            if (($emp['status'] ?? 'Active') === 'Active') {
                $employees[] = [
                    'employee_id' => $emp['employee_id'],
                    'full_name' => trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? '')),
                    'position' => $emp['position'] ?? 'Staff',
                    'department' => $emp['department'] ?? 'General'
                ];
            }
        }
        echo json_encode($employees);
    } else {
        echo json_encode(['error' => $data['message'] ?? 'Failed to fetch employees from workforce service']);
    }
} else {
    http_response_code($httpCode ?: 500);
    echo json_encode([
        'error' => 'Workforce service unreachable', 
        'code' => $httpCode,
        'url' => $url
    ]);
}
