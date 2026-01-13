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

$conn = getProcurementConnection();

$action = $_GET['action'] ?? $_POST['action'] ?? 'fetch';
$project_id = isset($_REQUEST['project_id']) ? intval($_REQUEST['project_id']) : 0;

// If no project_id in request, try session
if ($project_id <= 0 && isset($_SESSION['current_project_id'])) {
    $project_id = intval($_SESSION['current_project_id']);
}

$response = ['success' => false, 'message' => '', 'data' => []];

try {
    switch ($action) {
        case 'log':
            // Log a new generated report
            $report_type = $_POST['report_type'] ?? '';
            $report_name = $_POST['report_name'] ?? '';
            
            if (empty($report_type)) {
                throw new Exception('Report type is required');
            }
            
            // Generate report name if not provided
            if (empty($report_name)) {
                $report_titles = [
                    'inventory-status' => 'Inventory Status Report',
                    'purchase-orders' => 'Purchase Orders Report',
                    'stock-movement' => 'Stock Movement Report'
                ];
                $report_name = $report_titles[$report_type] ?? ucwords(str_replace('-', ' ', $report_type)) . ' Report';
            }
            
            // Get user name
            $generated_by = $_SESSION['user_name'] ?? 'Admin';
            
            // Check if table exists
            $tableExists = $conn->query("SHOW TABLES LIKE 'budget_generated_reports'");
            if (!$tableExists || $tableExists->num_rows == 0) {
                throw new Exception('Reports table does not exist');
            }
            
            // Insert the report log
            $stmt = $conn->prepare("INSERT INTO budget_generated_reports (report_type, report_name, project_id, generated_by, created_at) VALUES (?, ?, NULLIF(?,0), ?, NOW())");
            $stmt->bind_param("ssis", $report_type, $report_name, $project_id, $generated_by);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Report logged successfully';
                $response['data'] = [
                    'report_id' => $conn->insert_id,
                    'report_type' => $report_type,
                    'report_name' => $report_name,
                    'project_id' => $project_id,
                    'generated_by' => $generated_by
                ];
            } else {
                throw new Exception('Failed to log report: ' . $stmt->error);
            }
            $stmt->close();
            break;
            
        case 'fetch':
        default:
            // Fetch recent reports
            $tableExists = $conn->query("SHOW TABLES LIKE 'budget_generated_reports'");
            if (!$tableExists || $tableExists->num_rows == 0) {
                $response['success'] = true;
                $response['message'] = 'No reports table';
                $response['data'] = [];
                break;
            }
            
            // Build query for procurement report types only
            $report_sql = "SELECT r.report_id, r.report_type, r.report_name, r.project_id, 
                          COALESCE(p.project_name, 'All') AS project_name, 
                          r.generated_by, r.created_at 
                   FROM budget_generated_reports r 
                   LEFT JOIN icmis_projects p ON r.project_id = p.project_id 
                   WHERE r.report_type IN ('inventory-status', 'purchase-orders', 'stock-movement')";
            
            if ($project_id > 0) {
                $report_sql .= " AND (r.project_id = $project_id OR r.project_id IS NULL)";
            }
            $report_sql .= " ORDER BY r.created_at DESC LIMIT 20";
            
            $result = $conn->query($report_sql);
            $reports = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    // Format date for display
                    $row['formatted_date'] = date('M j, Y h:i A', strtotime($row['created_at']));
                    $reports[] = $row;
                }
            }
            
            $response['success'] = true;
            $response['message'] = 'Reports fetched successfully';
            $response['data'] = $reports;
            break;
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
