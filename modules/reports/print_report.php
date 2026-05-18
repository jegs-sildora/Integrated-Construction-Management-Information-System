<?php
/**
 * print_report.php
 * 
 * ICMIS - Integrated Construction Management Information System
 * 
 * Robust PHP script for the Reports Center that replaces jsPDF implementation.
 * Opens in a new tab (target="_blank") with HTML/CSS optimized for printing.
 * 
 * Usage: print_report.php?type=budget-summary&project_id=1
 * 
 * Report Types:
 * - Budget & Cost: budget-summary, expense-log, cash-flow
 * - Procurement: inventory-status, purchase-orders, stock-movement
 * - Project Mgmt: project-summary, phase-progress, task-status
 * - Labor: employee-roster, attendance-summary, payroll-report
 */

session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Logger.php';
require_once __DIR__ . '/../../core/ApiHelper.php';
require_once __DIR__ . '/../../core/ProjectContext.php';
require_once __DIR__ . '/../../config/database.php';

// Get request parameters
$report_type = $_GET['type'] ?? '';
$project_id = ProjectContext::getProjectId();

if (empty($report_type)) {
    die('Report type is required. Usage: print_report.php?type=budget-summary');
}

// Get project info if specified from API
$project = null;
$project_name = 'All Projects';
$project_code = 'N/A';

if ($project_id > 0) {
    $res_proj = ApiHelper::get('project/projects');
    if ($res_proj['status'] === 200) {
        foreach ($res_proj['data']['projects'] as $p) {
            if (intval($p['project_id']) === $project_id) {
                $project = $p;
                $project_name = $p['project_name'];
                $project_code = $p['project_code'];
                break;
            }
        }
    }
}
// User Info
$userName = $_SESSION['user_name'] ?? 'Admin';

// Report configuration
$report_config = [
    // Budget & Cost Reports
    'budget-summary' => [
        'title' => 'Budget Summary Report',
        'category' => 'Budget',
        'icon_color' => 'blue',
        'headers' => ['Category', 'Allocated', 'Spent', 'Remaining', 'Utilization']
    ],
    'expense-log' => [
        'title' => 'Expense Log Report',
        'category' => 'Budget',
        'icon_color' => 'green',
        'headers' => ['Date', 'Category', 'Description', 'Supplier', 'Status', 'Amount']
    ],
    'cash-flow' => [
        'title' => 'Cash Flow Analysis',
        'category' => 'Budget',
        'icon_color' => 'teal',
        'headers' => ['Month', 'Inflow', 'Outflow', 'Net Cash Flow', 'Cumulative']
    ],
    'inventory-status' => [
        'title' => 'Inventory Status Report',
        'category' => 'Procurement',
        'icon_color' => 'purple',
        'headers' => ['Item Code', 'Item Name', 'Category', 'In Stock', 'Unit', 'Valuation']
    ],
    'purchase-orders' => [
        'title' => 'Purchase Orders Report',
        'category' => 'Procurement',
        'icon_color' => 'indigo',
        'headers' => ['PO #', 'Date', 'Supplier', 'Status', 'Items', 'Total Amount']
    ],
    'stock-movement' => [
        'title' => 'Stock Movement Report',
        'category' => 'Procurement',
        'icon_color' => 'pink',
        'headers' => ['Date', 'Type', 'Item Name', 'Qty', 'Reference', 'Handled By']
    ],
    'project-summary' => [
        'title' => 'Project Summary Report',
        'category' => 'Project',
        'icon_color' => 'orange',
        'headers' => ['Metric', 'Current Value', 'Target', 'Variance', 'Status']
    ],
    'phase-progress' => [
        'title' => 'Phase Progress Report',
        'category' => 'Project',
        'icon_color' => 'amber',
        'headers' => ['Phase Name', 'Start Date', 'End Date', 'Duration', 'Progress']
    ],
    'task-status' => [
        'title' => 'Task Status Report',
        'category' => 'Project',
        'icon_color' => 'yellow',
        'headers' => ['Task Name', 'Assignee', 'Due Date', 'Priority', 'Status']
    ],
    'employee-roster' => [
        'title' => 'Employee Roster',
        'category' => 'Workforce',
        'icon_color' => 'cyan',
        'headers' => ['Code', 'Full Name', 'Position', 'Dept', 'Status', 'Hired Date']
    ],
    'attendance-summary' => [
        'title' => 'Attendance Summary',
        'category' => 'Workforce',
        'icon_color' => 'emerald',
        'headers' => ['Employee', 'Present', 'Absent', 'Late', 'Leave', 'Reliability']
    ],
    'payroll-report' => [
        'title' => 'Payroll Report',
        'category' => 'Workforce',
        'icon_color' => 'rose',
        'headers' => ['Employee', 'Basic Pay', 'Overtime', 'Deductions', 'Net Pay']
    ]
];

if (!isset($report_config[$report_type])) {
    die('Invalid report type.');
}

$config = $report_config[$report_type];
$report_title = $config['title'];
$report_category = $config['category'];

// --- SAVE REPORT METADATA TO MICROSERVICE ---
$report_data = [
    'project_id' => $project_id > 0 ? $project_id : null,
    'report_type' => $report_type,
    'category' => $report_category,
    'report_name' => $report_title,
    'generated_by' => $userName
];

ApiHelper::post('reports/reports', $report_data);
// ---------------------------------------------

// Validate report type
if (!isset($report_config[$report_type])) {
    die('Invalid report type: ' . htmlspecialchars($report_type) . '. Valid types: ' . implode(', ', array_keys($report_config)));
}

$config = $report_config[$report_type];
$report_title = $config['title'];
$report_category = $config['category'];
$headers = $config['headers'];
$rows = [];
$summary = [];

// ============================================
// FETCH DATA BASED ON REPORT TYPE
// ============================================
switch ($report_type) {
    // ========================================
    // BUDGET REPORTS
    // ========================================
    case 'budget-summary':
        // Fetch project details for budget allocation
        $total_allocated = 0;
        if ($project_id > 0) {
            $projRes = ApiHelper::get("project/projects/$project_id");
            if ($projRes['status'] === 200 && !empty($projRes['data'])) {
                $total_allocated = floatval($projRes['data']['total_budget'] ?? 0);
            }
        }

        // Fetch expenses from Budget Service
        $expRes = ApiHelper::get("budget/expenses?project_id=$project_id");
        $all_expenses = $expRes['data']['expenses'] ?? [];
        
        $total_spent = 0;
        $category_data = [];
        
        foreach ($all_expenses as $e) {
            $spent = floatval($e['amount']);
            $total_spent += $spent;
            $cat = $e['category'] ?: 'Uncategorized';
            if (!isset($category_data[$cat])) {
                $category_data[$cat] = 0;
            }
            $category_data[$cat] += $spent;
        }
        
        // Calculate allocation per category proportionally based on spending
        foreach ($category_data as $cat_name => $spent_amount) {
            $proportion = $total_spent > 0 ? ($spent_amount / $total_spent) : 0;
            $allocated = $total_allocated * $proportion;
            $allocated = max($allocated, $spent_amount);
            $remaining = $allocated - $spent_amount;
            $utilization = $allocated > 0 ? ($spent_amount / $allocated) * 100 : 0;
            
            $rows[] = [
                'category' => $cat_name,
                'allocated' => $allocated,
                'spent' => $spent_amount,
                'remaining' => $remaining,
                'utilization' => $utilization
            ];
        }
        
        $summary = [
            'Total Budget' => '₱' . number_format($total_allocated, 2),
            'Total Spent' => '₱' . number_format($total_spent, 2),
            'Remaining' => '₱' . number_format($total_allocated - $total_spent, 2),
            'Utilization' => $total_allocated > 0 ? number_format(($total_spent / $total_allocated) * 100, 1) . '%' : '0%'
        ];
        break;

    case 'expense-log':
        $res = ApiHelper::get("budget/expenses?project_id=$project_id&limit=100");
        $expenses = $res['data']['expenses'] ?? [];
        $total_amount = 0;
        
        foreach ($expenses as $e) {
            $total_amount += floatval($e['amount']);
            $rows[] = [
                'expense_id' => $e['expense_id'],
                'expense_date' => $e['expense_date'],
                'category' => $e['category'],
                'description' => $e['description'],
                'supplier_name' => $e['supplier_name'] ?? 'N/A',
                'amount' => $e['amount'],
                'status' => $e['status']
            ];
        }
        
        $summary = [
            'Total Records' => count($rows),
            'Total Amount' => '₱' . number_format($total_amount, 2)
        ];
        break;

    case 'cash-flow':
        // Get total budget as inflow reference
        $total_budget = 0;
        if ($project_id > 0 && $project) {
            $total_budget = floatval($project['total_budget']);
        } else {
            // Get sum of all projects from API
            $projRes = ApiHelper::get("project/projects");
            if ($projRes['status'] === 200) {
                foreach ($projRes['data']['projects'] ?? [] as $p) {
                    $total_budget += floatval($p['total_budget'] ?? 0);
                }
            }
        }
        
        // Fetch expenses from Budget Service
        $expRes = ApiHelper::get("budget/expenses?project_id=$project_id");
        $all_expenses = $expRes['data']['expenses'] ?? [];
        
        // Group by month
        $monthly_data = [];
        foreach ($all_expenses as $e) {
            $month = date('Y-m', strtotime($e['expense_date']));
            if (!isset($monthly_data[$month])) $monthly_data[$month] = 0;
            $monthly_data[$month] += floatval($e['amount']);
        }
        ksort($monthly_data);

        $cumulative = 0;
        $total_outflow = 0;
        $month_count = count($monthly_data);
        $monthly_budget = $month_count > 0 ? ($total_budget / max($month_count, 1)) : 0;
        
        foreach ($monthly_data as $month_year => $outflow) {
            $inflow = $monthly_budget;
            $net = $inflow - $outflow;
            $cumulative += $net;
            
            $monthName = date('F Y', strtotime($month_year . '-01'));
            $total_outflow += $outflow;
            
            $rows[] = [
                'month' => $monthName,
                'inflow' => $inflow,
                'outflow' => $outflow,
                'net' => $net,
                'cumulative' => $cumulative
            ];
        }
        // Reverse to show newest first
        $rows = array_reverse($rows);
        
        $summary = [
            'Total Budget' => '₱' . number_format($total_budget, 2),
            'Total Spent' => '₱' . number_format($total_outflow, 2),
            'Remaining' => '₱' . number_format($total_budget - $total_outflow, 2)
        ];
        break;

    // ========================================
    // PROCUREMENT REPORTS
    // ========================================
    case 'inventory-status':
        $res = ApiHelper::get("procurement/inventory?project_id=$project_id");
        $items = $res['data']['items'] ?? [];
        
        $low_stock_count = 0;
        $total_items = count($items);
        $total_value = 0;

        foreach ($items as $item) {
            $qty = intval($item['quantity']);
            $reorder = intval($item['reorder_level'] ?? 10);
            $unit_cost = floatval($item['unit_cost'] ?? 0);
            
            // Determine status based on quantity vs reorder level
            if ($qty <= 0) {
                $status = 'Out of Stock';
                $low_stock_count++;
            } elseif ($qty <= $reorder) {
                $status = 'Low Stock';
                $low_stock_count++;
            } elseif ($qty <= $reorder * 2) {
                $status = 'Normal';
            } else {
                $status = 'Well Stocked';
            }

            $total_value += $qty * $unit_cost;

            $rows[] = [
                'item_id' => $item['item_id'],
                'item_name' => $item['item_name'],
                'category' => $item['category'] ?: 'Uncategorized',
                'quantity' => $qty,
                'unit' => $item['unit'] ?: 'pcs',
                'status' => $status
            ];
        }
        
        $summary = [
            'Total Items' => $total_items,
            'Low/Out of Stock' => $low_stock_count,
            'Total Value' => '₱' . number_format($total_value, 2),
            'Stock Health' => $total_items > 0 ? number_format((($total_items - $low_stock_count) / $total_items) * 100, 1) . '%' : '100%'
        ];
        break;

    case 'purchase-orders':
        $res = ApiHelper::get("procurement/orders?project_id=$project_id&limit=50");
        $orders = $res['data']['orders'] ?? [];
        
        $total_amount = 0;
        $pending_count = 0;
        $delivered_count = 0;
        
        foreach ($orders as $o) {
            $total_amount += floatval($o['total_amount']);
            $status = strtolower($o['status'] ?? '');
            if ($status === 'pending' || $status === 'processing') $pending_count++;
            if ($status === 'delivered' || $status === 'completed' || $status === 'received') $delivered_count++;
            
            $rows[] = [
                'po_id' => $o['po_id'],
                'po_reference' => $o['po_reference'],
                'supplier_name' => $o['supplier_name'] ?? 'N/A',
                'order_date' => $o['order_date'],
                'item_count' => $o['item_count'] ?? 0,
                'total_amount' => $o['total_amount'],
                'status' => $o['status']
            ];
        }
        
        $summary = [
            'Total Orders' => count($rows),
            'Pending' => $pending_count,
            'Delivered' => $delivered_count,
            'Total Value' => '₱' . number_format($total_amount, 2)
        ];
        break;

    case 'stock-movement':
        $res = ApiHelper::get("procurement/reports?type=movement&project_id=$project_id&limit=100");
        $payload = $res['data']['data'] ?? $res['data'];
        $movements = $payload['rows'] ?? [];
        
        $total_movements = count($movements);
        $stock_in_count = 0;
        $stock_out_count = 0;
        $total_in_qty = 0;
        $total_out_qty = 0;

        foreach ($movements as $m) {
            // Note: ApiHelper returns arrays, but our previous logic expected object-like access.
            // Let's assume the rows are indexed by header position or associative.
            // In the centralized generator, we'll map them to a standard format.
            $type = $m['type'] ?? ($m[1] ?? ''); // Fallback to index if needed
            $qty = intval($m['quantity'] ?? ($m[3] ?? 0));

            if ($type === 'Stock In') {
                $stock_in_count++;
                $total_in_qty += $qty;
            } else {
                $stock_out_count++;
                $total_out_qty += $qty;
            }

            $rows[] = [
                'movement_date' => $m['movement_date'] ?? ($m[0] ?? ''),
                'item_name' => $m['item_name'] ?? ($m[2] ?? ''),
                'type' => $type,
                'quantity' => $qty,
                'reference' => $m['reference'] ?? ($m[4] ?? '-'),
                'handler' => $m['handler'] ?? ($m[5] ?? 'N/A')
            ];
        }

        $summary = [
            'Total Movements' => $total_movements,
            'Stock In' => $stock_in_count . ' (' . number_format($total_in_qty) . ' units)',
            'Stock Out' => $stock_out_count . ' (' . number_format($total_out_qty) . ' units)'
        ];
        break;

    // ========================================
    // PROJECT REPORTS
    // ========================================
    case 'project-summary':
        $res = ApiHelper::get("project/projects" . ($project_id > 0 ? "/$project_id" : ""));
        $projects = ($project_id > 0) ? [$res['data']] : ($res['data']['projects'] ?? []);
        
        $total_budget = 0;
        $active_count = 0;
        
        foreach ($projects as $p) {
            $total_budget += floatval($p['total_budget'] ?? 0);
            $status = strtolower($p['status'] ?? '');
            if ($status === 'active' || $status === 'in progress') {
                $active_count++;
            }
            $rows[] = [
                'project_id' => $p['project_id'],
                'project_name' => $p['project_name'],
                'project_code' => $p['project_code'],
                'location' => $p['location'] ?? 'N/A',
                'total_budget' => $p['total_budget'],
                'completion_rate' => $p['completion_rate'] ?? 0,
                'status' => $p['status'] ?? 'Active'
            ];
        }
        
        $summary = [
            'Total Projects' => count($rows),
            'Active Projects' => $active_count,
            'Total Budget' => '₱' . number_format($total_budget, 2)
        ];
        break;

    case 'phase-progress':
        $res = ApiHelper::get("project/phases?project_id=$project_id");
        $phases = $res['data']['phases'] ?? [];
        $completed_count = 0;
        
        foreach ($phases as $p) {
            if (strtolower($p['status'] ?? '') === 'completed') $completed_count++;
            $rows[] = [
                'phase_id' => $p['phase_id'],
                'phase_name' => $p['phase_name'],
                'project_name' => $p['project_name'] ?? $project_name,
                'start_date' => $p['start_date'],
                'end_date' => $p['end_date'],
                'duration' => $p['duration'] ?? 0,
                'status' => $p['status'] ?? 'Not Started'
            ];
        }
        
        $summary = [
            'Total Phases' => count($rows),
            'Completed' => $completed_count,
            'In Progress' => count($rows) - $completed_count
        ];
        break;

    case 'task-status':
        $res = ApiHelper::get("project/tasks?project_id=$project_id&limit=100");
        $tasks = $res['data']['tasks'] ?? [];
        $completed_count = 0;
        $overdue_count = 0;
        $today = date('Y-m-d');
        
        foreach ($tasks as $t) {
            $status = strtolower($t['status'] ?? '');
            if ($status === 'completed' || $status === 'done') $completed_count++;
            if (($t['due_date'] ?? '') && $t['due_date'] < $today && $status !== 'completed' && $status !== 'done') {
                $overdue_count++;
            }
            $rows[] = [
                'task_id' => $t['task_id'],
                'task_name' => $t['task_name'],
                'phase_name' => $t['phase_name'] ?? 'N/A',
                'assignee' => $t['assignee_name'] ?? 'Unassigned',
                'due_date' => $t['due_date'],
                'priority' => $t['priority'] ?? 'Normal',
                'status' => $t['status'] ?? 'Pending'
            ];
        }
        
        $summary = [
            'Total Tasks' => count($rows),
            'Completed' => $completed_count,
            'Overdue' => $overdue_count
        ];
        break;

    // ========================================
    // WORKFORCE REPORTS
    // ========================================
    case 'employee-roster':
        $res = ApiHelper::get("workforce/employees?project_id=$project_id&limit=1000");
        $employees = $res['data']['data'] ?? [];
        $active_count = 0;
        
        foreach ($employees as $e) {
            if (strtolower($e['status'] ?? 'active') === 'active') $active_count++;
            $rows[] = [
                'employee_id' => $e['employee_id'],
                'full_name' => ($e['first_name'] ?? '') . ' ' . ($e['last_name'] ?? ''),
                'position' => $e['job_title'] ?? 'N/A',
                'department' => $e['department'] ?? 'N/A',
                'phone' => $e['phone'] ?? '-',
                'status' => $e['status'] ?? 'Active'
            ];
        }
        
        $summary = [
            'Total Employees' => count($rows),
            'Active' => $active_count,
            'Inactive' => count($rows) - $active_count
        ];
        break;

    case 'attendance-summary':
        $res = ApiHelper::get("workforce/reports?type=attendance-summary&project_id=$project_id&limit=100");
        $data = $res['data']['data'] ?? [];
        $present_count = 0;
        $absent_count = 0;
        
        foreach ($data as $r) {
            $status = strtolower($r['status'] ?? 'present');
            if ($status === 'present' || $status === 'on time' || $status === 'late') {
                $present_count++;
            } else {
                $absent_count++;
            }
            
            $rows[] = [
                'employee_name' => $r['name'] ?? 'N/A',
                'attendance_date' => $r['date'] ?? date('Y-m-d'),
                'time_in' => $r['time_in'] ?? null,
                'time_out' => $r['time_out'] ?? null,
                'hours_worked' => $r['hours'] ?? 0,
                'status' => $r['status'] ?? 'Present'
            ];
        }
        
        $summary = [
            'Total Records' => count($rows),
            'Present' => $present_count,
            'Absent/Leave' => $absent_count
        ];
        break;

    case 'payroll-report':
        $res = ApiHelper::get("workforce/reports?type=payroll-report&project_id=$project_id&limit=100");
        $data = $res['data']['data'] ?? [];
        
        $total_gross = 0;
        $total_deductions = 0;
        $total_net = 0;
        
        foreach ($data as $r) {
            $total_gross += floatval($r['gross'] ?? 0);
            $total_deductions += floatval($r['ded'] ?? 0);
            $total_net += floatval($r['net'] ?? 0);
            
            $rows[] = [
                'employee_name' => $r['name'] ?? 'N/A',
                'period' => $r['period'] ?? 'Current',
                'hours_worked' => $r['hours'] ?? 0,
                'gross_pay' => $r['gross'] ?? 0,
                'deductions' => $r['ded'] ?? 0,
                'net_pay' => $r['net'] ?? 0
            ];
        }
        
        $summary = [
            'Total Gross Pay' => '₱' . number_format($total_gross, 2),
            'Total Deductions' => '₱' . number_format($total_deductions, 2),
            'Total Net Pay' => '₱' . number_format($total_net, 2)
        ];
        break;
}

// Helper function for status badge color
function getStatusColor($status) {
    $status = strtolower($status ?? '');
    return match(true) {
        str_contains($status, 'completed') || str_contains($status, 'approved') || str_contains($status, 'paid') || str_contains($status, 'active') => 'text-green-700 bg-green-50 border-green-200',
        str_contains($status, 'pending') || str_contains($status, 'in progress') || str_contains($status, 'processing') => 'text-blue-700 bg-blue-50 border-blue-200',
        str_contains($status, 'over') || str_contains($status, 'rejected') || str_contains($status, 'cancelled') || str_contains($status, 'overdue') || str_contains($status, 'low') => 'text-red-700 bg-red-50 border-red-200',
        str_contains($status, 'normal') || str_contains($status, 'late') => 'text-amber-700 bg-amber-50 border-amber-200',
        default => 'text-gray-700 bg-gray-50 border-gray-200'
    };
}

function getUtilizationColor($utilization) {
    if ($utilization > 100) return 'text-red-700';
    if ($utilization >= 90) return 'text-amber-700';
    if ($utilization >= 50) return 'text-blue-700';
    return 'text-green-700';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($report_title); ?> | ICMIS</title>
    
    <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>

    <style>
        /* Base Styles */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            padding: 40px;
        }

        .report-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        /* PRINT SPECIFIC STYLES */
        @media print {
            @page { margin: 0.5in; size: auto; }
            body { 
                background-color: white !important; 
                color: black !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .report-container {
                box-shadow: none !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: none !important;
            }

            /* Hide UI elements */
            .no-print { display: none !important; }
            
            /* Table Styling for Print */
            table { width: 100% !important; border-collapse: collapse !important; font-size: 10pt !important; }
            
            thead tr { background-color: #f3f4f6 !important; }
            thead th { 
                border: 1px solid #9ca3af !important; 
                padding: 8px !important; 
                color: black !important;
                font-weight: bold !important;
                text-transform: uppercase !important;
            }
            tbody td { 
                border: 1px solid #e5e7eb !important; 
                padding: 8px !important; 
                color: black !important;
            }

            /* Footer Spacing */
            .print-footer {
                margin-top: 50px !important;
                page-break-inside: avoid;
            }
        }

        /* Logo Sizing */
        .print-logo {
            height: 80px;
            width: auto;
            margin: 0 auto 10px auto;
            display: block;
        }
    </style>
</head>
<body>

    <div class="report-container">
        
        <!-- Report Header -->
        <div class="text-center border-b-2 border-slate-800 pb-6 mb-8">
            <img src="../../assets/images/nobg_logo.png" alt="ICMIS Logo" class="print-logo" onerror="this.style.display='none';">
            
            <h1 class="text-2xl font-black uppercase tracking-wide text-slate-900 mt-2"><?php echo htmlspecialchars($report_title); ?></h1>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-widest">Integrated Construction Management Information System</p>
            <p class="text-xs text-slate-400 mt-1">Generated on: <?php echo date('F j, Y h:i A'); ?></p>
        </div>

        <!-- Report Metadata -->
        <div class="grid grid-cols-2 gap-8 mb-8 text-sm">
            <div>
                <table class="w-full">
                    <tr>
                        <td class="font-bold text-slate-500 py-1 w-32">Project:</td>
                        <td class="font-bold text-slate-900 py-1"><?php echo htmlspecialchars($project_name); ?></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-500 py-1">Project Code:</td>
                        <td class="font-mono font-bold text-slate-700 py-1"><?php echo htmlspecialchars($project_code); ?></td>
                    </tr>
                </table>
            </div>
            <div>
                <table class="w-full">
                    <tr>
                        <td class="font-bold text-slate-500 py-1 w-32">Report Type:</td>
                        <td class="font-bold text-slate-900 py-1"><?php echo htmlspecialchars($report_category); ?></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-500 py-1">Generated By:</td>
                        <td class="text-slate-700 py-1"><?php echo htmlspecialchars($userName); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Summary Section -->
        <?php if (!empty($summary)): ?>
        <div class="mb-8 bg-slate-50 rounded-lg border border-slate-200 p-6">
            <h3 class="text-xs font-bold text-slate-500 uppercase mb-4">Report Summary</h3>
            <div class="grid grid-cols-<?php echo min(count($summary), 4); ?> gap-4 text-center">
                <?php $i = 0; foreach ($summary as $label => $value): $i++; ?>
                <div class="p-2 <?php echo $i < count($summary) ? 'border-r border-slate-200' : ''; ?>">
                    <p class="text-xs text-slate-500 uppercase font-bold"><?php echo htmlspecialchars($label); ?></p>
                    <p class="text-xl font-black text-slate-800 mt-1 font-mono"><?php echo htmlspecialchars($value); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Data Table -->
        <div class="mb-8">
            <h3 class="text-sm font-bold text-slate-900 uppercase border-b border-slate-200 pb-2 mb-4"><?php echo htmlspecialchars($report_title); ?> Details</h3>
            <?php if (count($rows) > 0): ?>
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-100">
                    <tr>
                        <?php foreach ($headers as $header): ?>
                        <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300"><?php echo htmlspecialchars($header); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                    <tr class="hover:bg-slate-50">
                        <?php
                        // Render cells based on report type
                        switch ($report_type):
                            case 'budget-summary':
                        ?>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-800 font-medium"><?php echo htmlspecialchars($row['category']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono">₱<?php echo number_format($row['allocated'], 2); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono text-blue-700">₱<?php echo number_format($row['spent'], 2); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono">₱<?php echo number_format($row['remaining'], 2); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center font-mono <?php echo getUtilizationColor($row['utilization']); ?>"><?php echo number_format($row['utilization'], 1); ?>%</td>
                        <?php break; case 'expense-log': ?>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-600"><?php echo date('M j, Y', strtotime($row['expense_date'])); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200"><span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200"><?php echo htmlspecialchars($row['category']); ?></span></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-700"><?php echo htmlspecialchars($row['description']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-600 italic"><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center"><span class="px-2 py-0.5 rounded text-xs font-bold uppercase border <?php echo getStatusColor($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono font-bold text-slate-900">₱<?php echo number_format($row['amount'], 2); ?></td>
                        <?php break; case 'cash-flow': ?>
                            <td class="px-4 py-2 text-sm border border-slate-200 font-medium text-slate-800"><?php echo htmlspecialchars($row['month']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono text-green-700">₱<?php echo number_format($row['inflow'], 2); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono text-red-700">₱<?php echo number_format($row['outflow'], 2); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono <?php echo $row['net'] >= 0 ? 'text-green-700' : 'text-red-700'; ?>">₱<?php echo number_format($row['net'], 2); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono font-bold <?php echo $row['cumulative'] >= 0 ? 'text-slate-900' : 'text-red-700'; ?>">₱<?php echo number_format($row['cumulative'], 2); ?></td>
                        <?php break; case 'inventory-status': ?>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-600 font-mono"><?php echo htmlspecialchars($row['item_id']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-800 font-medium"><?php echo htmlspecialchars($row['item_name']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200"><span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200"><?php echo htmlspecialchars($row['category']); ?></span></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center font-mono font-bold"><?php echo number_format($row['quantity']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-600"><?php echo htmlspecialchars($row['unit']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center"><span class="px-2 py-0.5 rounded text-xs font-bold uppercase border <?php echo getStatusColor($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                        <?php break; case 'purchase-orders': ?>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-800 font-mono font-medium"><?php echo htmlspecialchars($row['po_reference']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-700"><?php echo htmlspecialchars($row['supplier_name'] ?? 'N/A'); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-600"><?php echo date('M j, Y', strtotime($row['order_date'])); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center font-mono"><?php echo $row['item_count']; ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono font-bold">₱<?php echo number_format($row['total_amount'], 2); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center"><span class="px-2 py-0.5 rounded text-xs font-bold uppercase border <?php echo getStatusColor($row['status']); ?>"><?php echo htmlspecialchars(ucfirst($row['status'])); ?></span></td>
                        <?php break; case 'stock-movement': ?>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-600"><?php echo date('M j, Y', strtotime($row['movement_date'])); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-800 font-medium"><?php echo htmlspecialchars($row['item_name']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center"><span class="px-2 py-0.5 rounded text-xs font-bold uppercase border <?php echo $row['type'] === 'Stock In' ? 'text-green-700 bg-green-50 border-green-200' : 'text-orange-700 bg-orange-50 border-orange-200'; ?>"><?php echo htmlspecialchars($row['type']); ?></span></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center font-mono font-bold"><?php echo number_format($row['quantity']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-600 font-mono"><?php echo htmlspecialchars($row['reference']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-600"><?php echo htmlspecialchars($row['handler']); ?></td>
                        <?php break; case 'project-summary': ?>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-800 font-medium"><?php echo htmlspecialchars($row['project_name']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-600 font-mono"><?php echo htmlspecialchars($row['project_code']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-600"><?php echo htmlspecialchars($row['location'] ?? 'N/A'); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono font-bold">₱<?php echo number_format($row['total_budget'], 2); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center font-mono"><?php echo number_format($row['completion_rate'], 1); ?>%</td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center"><span class="px-2 py-0.5 rounded text-xs font-bold uppercase border <?php echo getStatusColor($row['status']); ?>"><?php echo htmlspecialchars(ucfirst($row['status'] ?? 'Active')); ?></span></td>
                        <?php break; case 'phase-progress': ?>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-800 font-medium"><?php echo htmlspecialchars($row['phase_name']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-600"><?php echo htmlspecialchars($row['project_name']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-600"><?php echo $row['start_date'] ? date('M j, Y', strtotime($row['start_date'])) : '-'; ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-600"><?php echo $row['end_date'] ? date('M j, Y', strtotime($row['end_date'])) : '-'; ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center font-mono"><?php echo ($row['duration'] ?? 0); ?> days</td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center"><span class="px-2 py-0.5 rounded text-xs font-bold uppercase border <?php echo getStatusColor($row['status']); ?>"><?php echo htmlspecialchars(ucfirst($row['status'] ?? 'Not Started')); ?></span></td>
                        <?php break; case 'task-status': ?>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-800 font-medium"><?php echo htmlspecialchars($row['task_name']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-600"><?php echo htmlspecialchars($row['phase_name']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-600"><?php echo htmlspecialchars($row['assignee']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-600"><?php echo $row['due_date'] ? date('M j, Y', strtotime($row['due_date'])) : '-'; ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center"><span class="px-2 py-0.5 rounded text-xs font-bold uppercase border <?php echo getStatusColor($row['priority']); ?>"><?php echo htmlspecialchars(ucfirst($row['priority'] ?? 'Normal')); ?></span></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center"><span class="px-2 py-0.5 rounded text-xs font-bold uppercase border <?php echo getStatusColor($row['status']); ?>"><?php echo htmlspecialchars(ucfirst($row['status'] ?? 'Pending')); ?></span></td>
                        <?php break; case 'employee-roster': ?>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-600 font-mono"><?php echo htmlspecialchars($row['employee_id']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-800 font-medium"><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-700"><?php echo htmlspecialchars($row['position']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200"><span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200"><?php echo htmlspecialchars($row['department']); ?></span></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-600 font-mono"><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center"><span class="px-2 py-0.5 rounded text-xs font-bold uppercase border <?php echo getStatusColor($row['status']); ?>"><?php echo htmlspecialchars(ucfirst($row['status'])); ?></span></td>
                        <?php break; case 'attendance-summary': ?>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-800 font-medium"><?php echo htmlspecialchars($row['employee_name']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-600"><?php echo date('M j, Y', strtotime($row['attendance_date'])); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center font-mono"><?php echo $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '-'; ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center font-mono"><?php echo $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '-'; ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center font-mono font-bold"><?php echo is_numeric($row['hours_worked']) ? $row['hours_worked'] . ' hrs' : '-'; ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center"><span class="px-2 py-0.5 rounded text-xs font-bold uppercase border <?php echo getStatusColor($row['status']); ?>"><?php echo htmlspecialchars(ucfirst($row['status'] ?? 'Present')); ?></span></td>
                        <?php break; case 'payroll-report': ?>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-800 font-medium"><?php echo htmlspecialchars($row['employee_name']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-600"><?php echo htmlspecialchars($row['period']); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-center font-mono"><?php echo number_format($row['hours_worked'], 1); ?> hrs</td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono">₱<?php echo number_format($row['gross_pay'], 2); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono text-red-600">₱<?php echo number_format($row['deductions'], 2); ?></td>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono font-bold text-green-700">₱<?php echo number_format($row['net_pay'], 2); ?></td>
                        <?php break; endswitch; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="px-4 py-8 text-center text-slate-500 italic border border-slate-200 bg-slate-50 rounded-lg">
                No data available for this report.
            </div>
            <?php endif; ?>
        </div>

        <!-- Print Footer with Signature Lines -->
        <div class="print-footer mt-12 pt-8">
            <div class="grid grid-cols-3 gap-8">
                <div class="text-center">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-12">Prepared By:</p>
                    <div class="border-b border-slate-800 w-3/4 mx-auto"></div>
                    <p class="text-sm font-bold mt-2 text-slate-900 uppercase"><?php echo htmlspecialchars($userName); ?></p>
                    <p class="text-xs text-slate-500">ICMIS Reporting</p>
                </div>

                <div class="text-center">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-12">Verified By:</p>
                    <div class="border-b border-slate-800 w-3/4 mx-auto"></div>
                    <p class="text-sm font-bold mt-2 text-slate-900 uppercase">Project Engineer</p> 
                    <p class="text-xs text-slate-500">Sign & Date</p>
                </div>

                <div class="text-center">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-12">Approved By:</p>
                    <div class="border-b border-slate-800 w-3/4 mx-auto"></div>
                    <p class="text-sm font-bold mt-2 text-slate-900 uppercase">Project Manager</p> 
                    <p class="text-xs text-slate-500">Sign & Date</p>
                </div>
            </div>
        </div>

        <!-- Print Button (Screen Only) -->
        <div class="no-print mt-8 text-center">
            <p class="text-sm text-gray-500 mb-4">Press the button below if printing does not start automatically.</p>
            <button onclick="window.print()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold shadow-md transition-colors">
                Print Report
            </button>
            <button onclick="window.close()" class="ml-3 px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold transition-colors">
                Close
            </button>
        </div>

    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            // Small delay to ensure styles and images are loaded
            setTimeout(() => {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
<?php
$conn->close();
?>
