<?php
// /modules/project/api/employees.php
// Returns employees available for task assignment via microservice

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

// Fetch employees from Workforce Microservice
$res = ApiHelper::get("workforce/employees?action=list");
$employees = [];

if ($res['status'] === 200 && isset($res['data']['data'])) {
    foreach ($res['data']['data'] as $emp) {
        if (($emp['status'] ?? 'Active') === 'Active') {
            $employees[] = [
                'employee_id' => $emp['employee_id'],
                'full_name' => $emp['first_name'] . ' ' . $emp['last_name'],
                // Fallback to title name from joined data if available
                'position' => $emp['job_title_name'] ?? 'Staff',
                'department' => $emp['department'] ?? 'General'
            ];
        }
    }
}

echo json_encode($employees);
?>