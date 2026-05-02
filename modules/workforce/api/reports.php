<?php
/**
 * Reports API - Workforce Module
 * Provides data for generating PDF reports via microservice.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'generate':
            generateReportData();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function generateReportData() {
    $type = $_REQUEST['type'] ?? '';
    $project_id = intval($_REQUEST['project_id'] ?? 0);
    $month = $_REQUEST['month'] ?? date('Y-m');
    
    if (!$project_id) {
        echo json_encode(['success' => false, 'message' => 'Project ID required']);
        return;
    }
    
    // 1. Fetch data via Workforce Microservice
    $res = ApiHelper::get("workforce/reports?type=$type&project_id=$project_id&month=$month");
    
    if ($res['status'] !== 200) {
        throw new Exception("Workforce Service Error: " . ($res['data']['message'] ?? 'Unknown error'));
    }

    $data = $res['data']['data'] ?? [];

    // 2. Log generation metadata via Reports Microservice
    $reportNames = [
        'employee-directory' => 'Employee Directory',
        'attendance-summary' => 'Attendance Summary (' . date('M Y', strtotime($month)) . ')',
        'assignment-report' => 'Assignment Report',
        'payroll-report' => 'Payroll Report (' . date('M Y', strtotime($month)) . ')',
        'workforce-analytics' => 'Workforce Analytics'
    ];

    $reportName = $reportNames[$type] ?? 'Workforce Report';
    $userName = $_SESSION['user_name'] ?? 'System';

    ApiHelper::post('reports/reports', [
        'project_id' => $project_id,
        'report_type' => $type,
        'category' => 'Workforce',
        'report_name' => $reportName,
        'generated_by' => $userName
    ]);

    echo json_encode(['success' => true, 'data' => $data]);
}
?>