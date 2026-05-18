<?php
/**
 * Fetch Recent Reports API - Procurement Module
 * ICMIS - Integrated Construction Management Information System
 * 
 * Handles fetching and logging of procurement reports
 * 
 * Actions:
 * - fetch: Get recent reports list
 * - log: Log a new generated report
 */

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../project_context.php';

require_once __DIR__ . '/../../../core/ApiHelper.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'fetch';
$project_id = $_REQUEST['project_id'] ?? ProjectContext::getProjectId();

$response = ['success' => false, 'message' => '', 'data' => []];

try {
    switch ($action) {
        case 'log':
            // Log a new generated report via Reports Service
            $report_type = $_POST['report_type'] ?? '';
            $report_name = $_POST['report_name'] ?? '';
            
            if (empty($report_type)) throw new Exception('Report type is required');
            
            $res = ApiHelper::call('reports/reports', 'POST', [
                'project_id' => $project_id,
                'report_type' => $report_type,
                'report_name' => $report_name,
                'category' => 'procurement',
                'generated_by' => $_SESSION['user_name'] ?? 'Admin'
            ]);
            
            if ($res['status'] === 200) {
                $response['success'] = true;
                $response['message'] = 'Report logged successfully';
            } else {
                throw new Exception('Failed to log report: ' . ($res['data']['message'] ?? 'Unknown error'));
            }
            break;
            
        case 'fetch':
        default:
            // Fetch recent procurement reports from Reports Service
            $res = ApiHelper::get("reports/reports?category=procurement&project_id=$project_id&limit=20");
            
            if ($res['status'] === 200) {
                $reports = $res['data']['reports'] ?? [];
                // Add formatted date for legacy JS compatibility
                foreach ($reports as &$r) {
                    $r['formatted_date'] = date('M j, Y h:i A', strtotime($r['created_at']));
                    $r['project_name'] = $r['project_name'] ?? 'All'; // Map if needed
                }
                $response['success'] = true;
                $response['message'] = 'Reports fetched successfully';
                $response['data'] = $reports;
            } else {
                $response['data'] = [];
                $response['success'] = true;
            }
            break;
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
