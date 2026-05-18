<?php
/**
 * Reports API - Generate Report
 * ICMIS - Integrated Construction Management Information System
 * 
 * Handles report generation requests and returns data for PDF generation
 */

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
require_once __DIR__ . '/../../../core/ProjectContext.php';

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$template = $input['template'] ?? '';
$project_id = intval($input['project_id'] ?? ProjectContext::getProjectId());

if (empty($template)) {
    echo json_encode(['success' => false, 'message' => 'Template type is required']);
    exit;
}

// Get project info if specified from API
$project = null;
if ($project_id > 0) {
    $res_proj = ApiHelper::get("project/projects/$project_id");
    if ($res_proj['status'] === 200) {
        $p = $res_proj['data']['project'] ?? null;
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

$data = [
    'project' => $project,
    'headers' => [],
    'rows' => []
];

try {
    switch ($template) {
        // ========================================
        // BUDGET REPORTS
        // ========================================
        case 'budget-summary':
            $data['headers'] = ['Category', 'Allocated', 'Spent', 'Remaining', 'Utilization'];
            
            $res = ApiHelper::get("budget/expenses?project_id=$project_id");
            $expenses = $res['data']['expenses'] ?? [];
            
            $cats = [];
            foreach ($expenses as $e) {
                $c = $e['category'] ?: 'Other';
                $cats[$c] = ($cats[$c] ?? 0) + floatval($e['amount']);
            }
            
            foreach ($cats as $cat => $spent) {
                $allocated = $spent * 1.2; // Placeholder
                $remaining = $allocated - $spent;
                $utilization = $allocated > 0 ? round(($spent / $allocated) * 100, 1) : 0;
                
                $data['rows'][] = [
                    $cat,
                    'PHP ' . number_format($allocated, 2),
                    'PHP ' . number_format($spent, 2),
                    'PHP ' . number_format($remaining, 2),
                    $utilization . '%'
                ];
            }
            break;

        case 'expense-log':
            $data['headers'] = ['Date', 'Category', 'Description', 'Supplier', 'Amount'];
            
            $res = ApiHelper::get("budget/expenses?project_id=$project_id&limit=100");
            $expenses = $res['data']['expenses'] ?? [];

            // Map supplier_id to supplier_name
            $supplierMap = [];
            $supRes = ApiHelper::get('procurement/suppliers?per_page=1000');
            if ($supRes['status'] === 200) {
                foreach (($supRes['data']['suppliers'] ?? []) as $sup) {
                    $supplierMap[$sup['supplier_id']] = $sup['supplier_name'];
                }
            }
            
            foreach ($expenses as $row) {
                $supplierName = $row['supplier_name'] ?? ($supplierMap[$row['supplier_id'] ?? null] ?? '-');
                $data['rows'][] = [
                    date('M j, Y', strtotime($row['expense_date'])),
                    $row['category'],
                    $row['description'],
                    $supplierName ?: '-',
                    'PHP ' . number_format($row['amount'], 2)
                ];
            }
            break;

        case 'cash-flow':
            $data['headers'] = ['Month', 'Inflow', 'Outflow', 'Net Cash Flow'];
            
            $res = ApiHelper::get("budget/expenses?project_id=$project_id");
            $expenses = $res['data']['expenses'] ?? [];
            
            $monthly = [];
            foreach ($expenses as $e) {
                $m = date('Y-m', strtotime($e['expense_date']));
                $monthly[$m] = ($monthly[$m] ?? 0) + floatval($e['amount']);
            }
            ksort($monthly);
            
            foreach ($monthly as $m => $outflow) {
                $inflow = $outflow * 1.1; // Placeholder
                $net = $inflow - $outflow;
                $data['rows'][] = [
                    date('F Y', strtotime($m . '-01')),
                    'PHP ' . number_format($inflow, 2),
                    'PHP ' . number_format($outflow, 2),
                    'PHP ' . number_format($net, 2)
                ];
            }
            break;

        // ========================================
        // PROCUREMENT REPORTS
        // ========================================
        case 'inventory-status':
            $data['headers'] = ['Item ID', 'Item Name', 'Category', 'In Stock', 'Unit', 'Status'];
            
            $res = ApiHelper::get("procurement/inventory?project_id=$project_id");
            $items = $res['data']['inventory'] ?? [];
            
            foreach ($items as $row) {
                $qty = intval($row['quantity']);
                $status = $qty <= 10 ? 'Low Stock' : ($qty <= 50 ? 'Normal' : 'Well Stocked');
                $data['rows'][] = [$row['item_id'], $row['item_name'], $row['category'], $qty, $row['unit'], $status];
            }
            break;

        case 'purchase-orders':
            $data['headers'] = ['PO Number', 'Supplier', 'Date', 'Total Amount', 'Status'];
            
            $res = ApiHelper::get("procurement/orders?project_id=$project_id&limit=50");
            $orders = $res['data']['orders'] ?? [];
            
            foreach ($orders as $row) {
                $data['rows'][] = [
                    $row['po_reference'],
                    $row['supplier_name'] ?: 'N/A',
                    date('M j, Y', strtotime($row['order_date'])),
                    'PHP ' . number_format($row['total_amount'], 2),
                    ucfirst($row['status'])
                ];
            }
            break;

        case 'stock-movement':
            $data['headers'] = ['Date', 'Item', 'Type', 'Quantity', 'PO Reference', 'Issued To/From'];
            
            $res = ApiHelper::get("procurement/reports?type=movement&project_id=$project_id&limit=50");
            $payload = $res['data']['data'] ?? $res['data'];
            $movements = $payload['rows'] ?? [];

            $employeeMap = [];
            $empUrl = $project_id > 0 ? "workforce/employees?project_id=$project_id&limit=1000" : "workforce/employees?limit=1000";
            $empRes = ApiHelper::get($empUrl);
            foreach (($empRes['data']['data'] ?? []) as $emp) {
                $employeeMap[$emp['employee_id']] = trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
            }
            
            foreach ($movements as $row) {
                $issuedToId = $row['issued_to_employee_id'] ?? null;
                $handler = $row['handler'] ?? '-';
                if ($issuedToId && !empty($employeeMap[$issuedToId])) {
                    $handler = $employeeMap[$issuedToId];
                }
                $reference = $row['reference'] ?? ($row[4] ?? null);
                $data['rows'][] = [
                    date('M j, Y', strtotime($row['movement_date'] ?? $row[0])),
                    $row['item_name'] ?? $row[2],
                    $row['type'] ?? $row[1],
                    $row['quantity'] ?? $row[3],
                    $reference ?: '-',
                    $handler ?: '-'
                ];
            }
            break;

        // ========================================
        // PROJECT REPORTS
        // ========================================
        case 'project-summary':
            $data['headers'] = ['Project', 'Code', 'Location', 'Budget', 'Status'];
            
            $res = ApiHelper::get("project/projects" . ($project_id > 0 ? "/$project_id" : ""));
            $projects = ($project_id > 0) ? [($res['data']['project'] ?? null)] : ($res['data']['projects'] ?? []);
            
            foreach ($projects as $row) {
                if (!$row) {
                    continue;
                }
                $data['rows'][] = [
                    $row['project_name'], $row['project_code'], $row['location'] ?: 'N/A',
                    'PHP ' . number_format($row['total_budget'], 2), ucfirst($row['status'] ?? 'Active')
                ];
            }
            break;

        case 'phase-progress':
            $data['headers'] = ['Phase', 'Project', 'Start Date', 'End Date', 'Duration', 'Status'];
            
            $res = ApiHelper::get("project/phases?project_id=$project_id");
            $phases = $res['data']['phases'] ?? [];
            
            foreach ($phases as $row) {
                $data['rows'][] = [
                    $row['phase_name'], $row['project_name'] ?? 'N/A',
                    $row['start_date'] ? date('M j, Y', strtotime($row['start_date'])) : '-',
                    $row['end_date'] ? date('M j, Y', strtotime($row['end_date'])) : '-',
                    ($row['duration'] ?? 0) . ' days', ucfirst($row['status'] ?? 'Not Started')
                ];
            }
            break;

        case 'task-status':
            $data['headers'] = ['Task', 'Phase', 'Assignee', 'Due Date', 'Priority', 'Status'];
            
            $res = ApiHelper::get("project/tasks?project_id=$project_id&limit=100");
            $tasks = $res['data']['tasks'] ?? [];

            $employeeMap = [];
            $empUrl = $project_id > 0 ? "workforce/employees?project_id=$project_id&limit=1000" : "workforce/employees?limit=1000";
            $empRes = ApiHelper::get($empUrl);
            foreach (($empRes['data']['data'] ?? []) as $emp) {
                $employeeMap[$emp['employee_id']] = trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
            }
            
            foreach ($tasks as $row) {
                $assigneeId = $row['assigned_to_employee_id'] ?? null;
                $assigneeName = $row['assignee_name'] ?? null;
                if (!$assigneeName && $assigneeId && !empty($employeeMap[$assigneeId])) {
                    $assigneeName = $employeeMap[$assigneeId];
                }
                $data['rows'][] = [
                    $row['task_name'], $row['phase_name'] ?: 'N/A', $assigneeName ?: 'Unassigned',
                    $row['due_date'] ? date('M j, Y', strtotime($row['due_date'])) : '-',
                    ucfirst($row['priority'] ?? 'Normal'), ucfirst($row['status'] ?? 'Pending')
                ];
            }
            break;

        // ========================================
        // WORKFORCE REPORTS
        // ========================================
        case 'employee-roster':
            $data['headers'] = ['Employee ID', 'Name', 'Position', 'Department', 'Contact', 'Status'];
            
            $res = ApiHelper::get("workforce/employees?project_id=$project_id&limit=1000");
            $employees = $res['data']['data'] ?? [];
            
            foreach ($employees as $row) {
                $position = $row['position'] ?? $row['job_title'] ?? 'N/A';
                $department = $row['department'] ?? $row['role'] ?? 'N/A';
                $contact = $row['phone'] ?? $row['email'] ?? '-';
                $data['rows'][] = [
                    $row['employee_id'], ($row['first_name'] . ' ' . $row['last_name']),
                    $position ?: 'N/A', $department ?: 'N/A',
                    $contact ?: '-', ucfirst($row['status'] ?? 'Active')
                ];
            }
            break;

        case 'attendance-summary':
            $data['headers'] = ['Employee', 'Present Days', 'Absent Days', 'Leave Days', 'Attendance Rate'];
            
            $res = ApiHelper::get("workforce/reports?type=attendance-summary&project_id=$project_id&limit=100");
            $att = $res['data']['attendance'] ?? [];
            
            foreach ($att as $row) {
                $data['rows'][] = [
                    $row['employee_name'],
                    $row['present_days'] ?? 0,
                    $row['absent_days'] ?? 0,
                    $row['leave_days'] ?? 0,
                    ($row['attendance_rate'] ?? 0) . '%'
                ];
            }
            break;

        case 'payroll-report':
            $data['headers'] = ['Employee', 'Job Title', 'Days Worked', 'Daily Rate', 'Gross Pay', 'Net Pay'];
            
            $res = ApiHelper::get("workforce/reports?type=payroll-report&project_id=$project_id&limit=100");
            $payroll = $res['data']['payroll'] ?? [];
            
            foreach ($payroll as $row) {
                $data['rows'][] = [
                    $row['employee_name'], $row['job_title'] ?? 'N/A', $row['days_worked'] ?? 0,
                    'PHP ' . number_format($row['daily_rate'] ?? 0, 2),
                    'PHP ' . number_format($row['gross_pay'] ?? 0, 2),
                    'PHP ' . number_format($row['net_pay'] ?? 0, 2)
                ];
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown template type: ' . $template]);
            exit;
    }

    // Log the report generation via Reports Service
    $userName = $_SESSION['user_name'] ?? 'Admin';
    ApiHelper::call('reports/reports', 'POST', [
        'project_id' => $project_id,
        'report_type' => $template,
        'report_name' => ucwords(str_replace('-', ' ', $template)),
        'category' => explode('-', $template)[0],
        'generated_by' => $userName
    ]);

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error generating report: ' . $e->getMessage()]);
}
?>
