<?php
error_reporting(0);
ini_set('display_errors', 0);
/**
 * ========================= API: Generate Report =========================
 * Purpose: Aggregates data from multiple microservices to generate 
 * structured report data (headers and rows) for the frontend.
 * ============================================================================ 
 */

header('Content-Type: application/json');

// Helper for internal service calls
function callService($service, $endpoint, $method = 'GET', $data = null) {
    $baseUrl = getenv(strtoupper($service) . '_SERVICE_URL') ?: "http://$service-service";
    
    // Split endpoint into path and query
    $parts = explode('?', $endpoint);
    $path = $parts[0];
    $query = isset($parts[1]) ? '?' . $parts[1] : '';
    
    $url = "$baseUrl/api/v1/{$path}.php$query";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'], 'Content-Type: application/json']);
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    return [
        'status' => $status,
        'data' => json_decode($response, true)
    ];
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$template = $input['template'] ?? '';
$project_id = intval($input['project_id'] ?? 0);

if (empty($template)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Template type is required']);
    exit;
}

// 1. Get Project Info
$project = null;
if ($project_id > 0) {
    $res = callService('project', 'projects', 'GET', null);
    if ($res['status'] === 200) {
        // Find the specific project in the list or fetch by ID if service supports it
        // Our project service supports fetch_id
        $resSingle = callService('project', "projects?fetch_id=$project_id");
        if ($resSingle['status'] === 200) {
            $p = $resSingle['data']['project'] ?? null;
            if ($p) {
                $project = [
                    'id' => $p['project_id'],
                    'name' => $p['project_name'],
                    'code' => $p['project_code'],
                    'location' => $p['location'] ?? 'N/A',
                    'total_budget' => floatval($p['total_budget']),
                    'actual_spending' => floatval($p['actual_spending'] ?? 0)
                ];
            }
        }
    }
}

$data = [
    'project' => $project,
    'headers' => [],
    'rows' => []
];

try {
    switch ($template) {
        case 'budget-summary':
            $data['headers'] = ['Category', 'Allocated', 'Spent', 'Remaining', 'Utilization'];
            $res = callService('budget', "expenses?project_id=$project_id");
            $expenses = $res['data']['expenses'] ?? [];
            
            $cats = [];
            foreach ($expenses as $e) {
                $c = $e['category'] ?: 'Other';
                $cats[$c] = ($cats[$c] ?? 0) + floatval($e['amount']);
            }
            
            foreach ($cats as $cat => $spent) {
                $allocated = $spent * 1.2; 
                $remaining = $allocated - $spent;
                $utilization = $allocated > 0 ? round(($spent / $allocated) * 100, 1) : 0;
                $data['rows'][] = [$cat, 'PHP ' . number_format($allocated, 2), 'PHP ' . number_format($spent, 2), 'PHP ' . number_format($remaining, 2), $utilization . '%'];
            }
            break;

        case 'expense-log':
            $data['headers'] = ['Date', 'Category', 'Description', 'Supplier', 'Amount'];
            $res = callService('budget', "expenses?project_id=$project_id");
            $expenses = $res['data']['expenses'] ?? [];
            foreach ($expenses as $row) {
                $data['rows'][] = [
                    date('M j, Y', strtotime($row['expense_date'])),
                    $row['category'],
                    $row['description'],
                    $row['supplier_name'] ?? '-',
                    'PHP ' . number_format($row['amount'], 2)
                ];
            }
            break;

        case 'inventory-status':
            $data['headers'] = ['Item ID', 'Item Name', 'Category', 'In Stock', 'Unit', 'Status'];
            $res = callService('procurement', "inventory?project_id=$project_id");
            $items = $res['data']['inventory'] ?? [];
            foreach ($items as $row) {
                $qty = intval($row['quantity']);
                $status = $qty <= 10 ? 'Low Stock' : ($qty <= 50 ? 'Normal' : 'Well Stocked');
                $data['rows'][] = [$row['item_id'], $row['item_name'], $row['category'], $qty, $row['unit'], $status];
            }
            break;

        case 'project-summary':
            $data['headers'] = ['Project', 'Code', 'Location', 'Budget', 'Status'];
            $res = callService('project', "projects" . ($project_id > 0 ? "?fetch_id=$project_id" : ""));
            $projects = $project_id > 0 ? [$res['data']['project']] : $res['data']['projects'];
            foreach ($projects as $row) {
                if (!$row) continue;
                $data['rows'][] = [
                    $row['project_name'], $row['project_code'], $row['location'] ?: 'N/A',
                    'PHP ' . number_format($row['total_budget'], 2), ucfirst($row['status'] ?? 'Active')
                ];
            }
            break;

        case 'employee-roster':
            $data['headers'] = ['Employee ID', 'Name', 'Position', 'Department', 'Contact', 'Status'];
            $res = callService('workforce', "employees?project_id=$project_id");
            $employees = $res['data']['data'] ?? [];
            foreach ($employees as $row) {
                $data['rows'][] = [
                    $row['employee_id'], ($row['first_name'] . ' ' . $row['last_name']),
                    $row['position'] ?: 'N/A', $row['department'] ?: 'N/A',
                    $row['phone'] ?: ($row['email'] ?: '-'), ucfirst($row['status'] ?? 'Active')
                ];
            }
            break;

        default:
            // More templates can be added here as needed
            $data['headers'] = ['Template Not Fully Ported'];
            $data['rows'][] = ["The '$template' template logic is being migrated."];
            break;
    }

    // Record metadata in Reports Service
    callService('reports', 'reports', 'POST', [
        'project_id' => $project_id,
        'report_type' => $template,
        'report_name' => ucwords(str_replace('-', ' ', $template)),
        'category' => explode('-', $template)[0],
        'generated_by' => $_SERVER['HTTP_X_USER_NAME'] ?? 'System'
    ]);

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
