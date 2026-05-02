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
require_once __DIR__ . '/../../config/database.php';

// Get request parameters
$report_type = $_GET['type'] ?? '';
$project_id = intval($_GET['project_id'] ?? 0);

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
        // Get total budget from project or sum of approved proposals
        $total_allocated = 0;
        if ($project_id > 0 && $project) {
            $total_allocated = floatval($project['total_budget']);
        } else {
            // Sum all approved proposals
            $alloc_sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM budget_proposals WHERE status = 'APPROVED'";
            $alloc_result = $conn->query($alloc_sql);
            if ($alloc_result) {
                $total_allocated = floatval($alloc_result->fetch_assoc()['total']);
            }
        }
        
        // Get expenses grouped by category
        $sql = "SELECT 
                    e.category,
                    SUM(e.amount) as spent,
                    COUNT(*) as expense_count
                FROM budget_expenses e
                WHERE 1=1";
        
        if ($project_id > 0) {
            $sql .= " AND e.project_id = ?";
        }
        $sql .= " GROUP BY e.category ORDER BY spent DESC";
        
        $stmt = $conn->prepare($sql);
        if ($project_id > 0) {
            $stmt->bind_param("i", $project_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $total_spent = 0;
        $category_data = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $spent = floatval($row['spent']);
                $total_spent += $spent;
                $category_data[] = [
                    'category' => $row['category'] ?: 'Uncategorized',
                    'spent' => $spent
                ];
            }
        }
        
        // Calculate allocation per category proportionally based on spending
        foreach ($category_data as $cat) {
            $proportion = $total_spent > 0 ? ($cat['spent'] / $total_spent) : 0;
            $allocated = $total_allocated * $proportion;
            // Ensure allocated is at least equal to spent for display purposes
            $allocated = max($allocated, $cat['spent']);
            $remaining = $allocated - $cat['spent'];
            $utilization = $allocated > 0 ? ($cat['spent'] / $allocated) * 100 : 0;
            
            $rows[] = [
                'category' => $cat['category'],
                'allocated' => $allocated,
                'spent' => $cat['spent'],
                'remaining' => $remaining,
                'utilization' => $utilization
            ];
        }
        
        // Recalculate totals from rows
        $total_allocated_display = array_sum(array_column($rows, 'allocated'));
        
        $summary = [
            'Total Budget' => '₱' . number_format($total_allocated, 2),
            'Total Spent' => '₱' . number_format($total_spent, 2),
            'Remaining' => '₱' . number_format($total_allocated - $total_spent, 2),
            'Utilization' => $total_allocated > 0 ? number_format(($total_spent / $total_allocated) * 100, 1) . '%' : '0%'
        ];
        break;

    case 'expense-log':
        $sql = "SELECT e.expense_id, e.expense_date, e.category, e.description, 
                       COALESCE(s.supplier_name, 'N/A') as supplier_name, e.amount, e.status 
                FROM budget_expenses e
                LEFT JOIN procurement_suppliers s ON e.supplier_id = s.supplier_id
                WHERE 1=1";
        if ($project_id > 0) {
            $sql .= " AND e.project_id = ?";
        }
        $sql .= " ORDER BY e.expense_date DESC LIMIT 100";
        
        $stmt = $conn->prepare($sql);
        if ($project_id > 0) {
            $stmt->bind_param("i", $project_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $total_amount = 0;
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $total_amount += floatval($row['amount']);
                $rows[] = $row;
            }
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
            $budget_sql = "SELECT COALESCE(SUM(total_budget), 0) as total FROM icmis_projects";
            $budget_result = $conn->query($budget_sql);
            if ($budget_result) {
                $total_budget = floatval($budget_result->fetch_assoc()['total']);
            }
        }
        
        // Get monthly expenses
        $sql = "SELECT 
                    DATE_FORMAT(expense_date, '%Y-%m') as month_year,
                    SUM(amount) as monthly_total
                FROM budget_expenses 
                WHERE 1=1";
        if ($project_id > 0) {
            $sql .= " AND project_id = ?";
        }
        $sql .= " GROUP BY DATE_FORMAT(expense_date, '%Y-%m') ORDER BY month_year ASC LIMIT 12";
        
        $stmt = $conn->prepare($sql);
        if ($project_id > 0) {
            $stmt->bind_param("i", $project_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $cumulative = 0;
        $total_outflow = 0;
        
        if ($result) {
            $month_count = $result->num_rows;
            $monthly_budget = $month_count > 0 ? ($total_budget / max($month_count, 1)) : 0;
            
            while ($row = $result->fetch_assoc()) {
                $outflow = floatval($row['monthly_total']);
                // Use proportional budget as "inflow" (budget allocation per month)
                $inflow = $monthly_budget;
                $net = $inflow - $outflow;
                $cumulative += $net;
                
                $dateParts = explode('-', $row['month_year']);
                $monthName = date('F Y', mktime(0, 0, 0, $dateParts[1], 1, $dateParts[0]));
                
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
        }
        
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
        // Check which columns exist in procurement_inventory
        $hasReorder = false;
        $hasUnitCost = false;
        $hasProjectId = false;
        
        $colCheck = $conn->query("SHOW COLUMNS FROM procurement_inventory");
        if ($colCheck) {
            while ($col = $colCheck->fetch_assoc()) {
                if ($col['Field'] === 'reorder_level') $hasReorder = true;
                if ($col['Field'] === 'unit_cost') $hasUnitCost = true;
                if ($col['Field'] === 'project_id') $hasProjectId = true;
            }
        }

        // Build dynamic SQL based on available columns
        $selectCols = "item_id, item_name, category, quantity, unit";
        if ($hasReorder) $selectCols .= ", reorder_level";
        if ($hasUnitCost) $selectCols .= ", unit_cost";
        
        $sql = "SELECT $selectCols FROM procurement_inventory WHERE 1=1";
        if ($project_id > 0 && $hasProjectId) {
            $sql .= " AND project_id = ?";
        }
        $sql .= " ORDER BY category, item_name";

        $stmt = $conn->prepare($sql);
        if ($project_id > 0 && $hasProjectId) {
            $stmt->bind_param("i", $project_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $low_stock_count = 0;
        $total_items = 0;
        $total_value = 0;

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $qty = intval($row['quantity']);
                $reorder = isset($row['reorder_level']) ? intval($row['reorder_level']) : 10;
                $unit_cost = isset($row['unit_cost']) ? floatval($row['unit_cost']) : 0;
                
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

                $total_items++;
                $total_value += $qty * $unit_cost;

                $rows[] = [
                    'item_id' => $row['item_id'],
                    'item_name' => $row['item_name'],
                    'category' => $row['category'] ?: 'Uncategorized',
                    'quantity' => $qty,
                    'unit' => $row['unit'] ?: 'pcs',
                    'status' => $status
                ];
            }
        }
        
        $summary = [
            'Total Items' => $total_items,
            'Low/Out of Stock' => $low_stock_count,
            'Total Value' => '₱' . number_format($total_value, 2),
            'Stock Health' => $total_items > 0 ? number_format((($total_items - $low_stock_count) / $total_items) * 100, 1) . '%' : '100%'
        ];
        break;

    case 'purchase-orders':
        // Check if project_id column exists
        $hasProjectId = false;
        $colCheck = $conn->query("SHOW COLUMNS FROM procurement_purchase_orders LIKE 'project_id'");
        if ($colCheck && $colCheck->num_rows > 0) {
            $hasProjectId = true;
        }
        
        $sql = "SELECT po.po_id, po.po_reference, COALESCE(s.supplier_name, 'N/A') as supplier_name, 
                       po.order_date, po.total_amount, po.status,
                       (SELECT COUNT(*) FROM procurement_purchase_order_items poi WHERE poi.po_id = po.po_id) as item_count
                FROM procurement_purchase_orders po
                LEFT JOIN procurement_suppliers s ON po.supplier_id = s.supplier_id
                WHERE 1=1";
        if ($project_id > 0 && $hasProjectId) {
            $sql .= " AND po.project_id = ?";
        }
        $sql .= " ORDER BY po.order_date DESC LIMIT 50";
        
        $stmt = $conn->prepare($sql);
        if ($project_id > 0 && $hasProjectId) {
            $stmt->bind_param("i", $project_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $total_amount = 0;
        $pending_count = 0;
        $delivered_count = 0;
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $total_amount += floatval($row['total_amount']);
                $status = strtolower($row['status'] ?? '');
                if ($status === 'pending' || $status === 'processing') $pending_count++;
                if ($status === 'delivered' || $status === 'completed' || $status === 'received') $delivered_count++;
                $rows[] = $row;
            }
        }
        
        $summary = [
            'Total Orders' => count($rows),
            'Pending' => $pending_count,
            'Delivered' => $delivered_count,
            'Total Value' => '₱' . number_format($total_amount, 2)
        ];
        break;

    case 'stock-movement':
        $movements = [];
        
        // Check if project_id columns exist
        $hasProjectIdIn = false;
        $hasProjectIdOut = false;
        $colCheck = $conn->query("SHOW COLUMNS FROM procurement_stock_in LIKE 'project_id'");
        if ($colCheck && $colCheck->num_rows > 0) $hasProjectIdIn = true;
        $colCheck = $conn->query("SHOW COLUMNS FROM procurement_stock_out LIKE 'project_id'");
        if ($colCheck && $colCheck->num_rows > 0) $hasProjectIdOut = true;
        
        // Stock In
        $sql = "SELECT si.date_received as movement_date, COALESCE(i.item_name, 'Unknown') as item_name, 
                       'Stock In' as type, si.quantity_received as quantity, 
                       COALESCE(po.po_reference, '-') as reference, 'Received' as handler
                FROM procurement_stock_in si
                LEFT JOIN procurement_purchase_orders po ON si.po_id = po.po_id
                LEFT JOIN procurement_inventory i ON si.item_id = i.item_id
                WHERE 1=1";
        if ($project_id > 0 && $hasProjectIdIn) {
            $sql .= " AND si.project_id = ?";
        }
        $sql .= " ORDER BY si.date_received DESC LIMIT 50";
        
        $stmt = $conn->prepare($sql);
        if ($project_id > 0 && $hasProjectIdIn) {
            $stmt->bind_param("i", $project_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $movements[] = $row;
            }
        }
        
        // Stock Out - detect which quantity column exists to avoid SQL errors
        $qtyCandidates = ['quantity', 'qty', 'quantity_issued', 'issued_qty'];
        $foundCols = [];
        foreach ($qtyCandidates as $c) {
            $colCheckQty = $conn->query("SHOW COLUMNS FROM procurement_stock_out LIKE '$c'");
            if ($colCheckQty && $colCheckQty->num_rows > 0) $foundCols[] = "so.$c";
        }
        $qtyExpr = !empty($foundCols) ? 'COALESCE(' . implode(', ', $foundCols) . ', 0)' : '0';

        // Build Stock Out SELECT using the discovered quantity expression
        $sql = "SELECT so.date_issued as movement_date, COALESCE(i.item_name, 'Unknown') as item_name,
                       'Stock Out' as type, $qtyExpr as quantity, '-' as reference,
                       COALESCE(CONCAT(emp.first_name, ' ', emp.last_name), 'N/A') as handler
                FROM procurement_stock_out so
                LEFT JOIN procurement_inventory i ON so.item_id = i.item_id
                LEFT JOIN workforce_employees emp ON so.issued_to_employee_id = emp.employee_id
                WHERE 1=1";
        if ($project_id > 0 && $hasProjectIdOut) {
            $sql .= " AND so.project_id = ?";
        }
        $sql .= " ORDER BY so.date_issued DESC LIMIT 50";
        
        $stmt = $conn->prepare($sql);
        if ($project_id > 0 && $hasProjectIdOut) {
            $stmt->bind_param("i", $project_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $movements[] = $row;
            }
        }
        
        // Sort by date (newest first)
        usort($movements, function($a, $b) {
            return strtotime($b['movement_date']) - strtotime($a['movement_date']);
        });
        
        // Compute totals using the complete movements list, then slice for display
        $total_movements = count($movements);
        $stock_in_count = count(array_filter($movements, function($r){ return ($r['type'] ?? '') === 'Stock In'; }));
        $stock_out_count = count(array_filter($movements, function($r){ return ($r['type'] ?? '') === 'Stock Out'; }));

        // Calculate total quantities across all movements
        $total_in_qty = array_sum(array_map(function($r){ return (($r['type'] ?? '') === 'Stock In') ? intval($r['quantity'] ?? 0) : 0; }, $movements));
        $total_out_qty = array_sum(array_map(function($r){ return (($r['type'] ?? '') === 'Stock Out') ? intval($r['quantity'] ?? 0) : 0; }, $movements));

        $rows = array_slice($movements, 0, 50);

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
        $sql = "SELECT p.project_id, p.project_name, p.project_code, p.location, p.total_budget, 
                       p.status, COALESCE(p.completion_rate, 0) as completion_rate
                FROM icmis_projects p";
        if ($project_id > 0) {
            $sql .= " WHERE p.project_id = ?";
        }
        $sql .= " ORDER BY p.project_id DESC";
        
        $stmt = $conn->prepare($sql);
        if ($project_id > 0) {
            $stmt->bind_param("i", $project_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $total_budget = 0;
        $active_count = 0;
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $total_budget += floatval($row['total_budget']);
                if (strtolower($row['status'] ?? '') === 'active' || strtolower($row['status'] ?? '') === 'in progress') {
                    $active_count++;
                }
                $rows[] = $row;
            }
        }
        
        $summary = [
            'Total Projects' => count($rows),
            'Active Projects' => $active_count,
            'Total Budget' => '₱' . number_format($total_budget, 2)
        ];
        break;

    case 'phase-progress':
        $sql = "SELECT pp.phase_id, pp.phase_name, p.project_name, pp.start_date, pp.end_date, 
                       pp.duration, pp.status
                FROM icmis_project_phases pp
                LEFT JOIN icmis_projects p ON pp.project_id = p.project_id
                WHERE 1=1";
        if ($project_id > 0) {
            $sql .= " AND pp.project_id = ?";
        }
        $sql .= " ORDER BY pp.start_date";
        
        $stmt = $conn->prepare($sql);
        if ($project_id > 0) {
            $stmt->bind_param("i", $project_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $completed_count = 0;
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (strtolower($row['status'] ?? '') === 'completed') $completed_count++;
                $rows[] = $row;
            }
        }
        
        $summary = [
            'Total Phases' => count($rows),
            'Completed' => $completed_count,
            'In Progress' => count($rows) - $completed_count
        ];
        break;

    case 'task-status':
        $sql = "SELECT t.task_id, t.task_name, COALESCE(pp.phase_name, 'N/A') as phase_name,
                       COALESCE(CONCAT(e.first_name, ' ', e.last_name), 'Unassigned') as assignee,
                       t.due_date, t.priority, t.status
                FROM icmis_tasks t
                LEFT JOIN icmis_project_phases pp ON t.phase_id = pp.phase_id
                LEFT JOIN workforce_employees e ON t.assigned_to_employee_id = e.employee_id
                WHERE 1=1";
        if ($project_id > 0) {
            $sql .= " AND t.project_id = ?";
        }
        $sql .= " ORDER BY t.due_date LIMIT 100";
        
        $stmt = $conn->prepare($sql);
        if ($project_id > 0) {
            $stmt->bind_param("i", $project_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $completed_count = 0;
        $overdue_count = 0;
        $today = date('Y-m-d');
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $status = strtolower($row['status'] ?? '');
                if ($status === 'completed' || $status === 'done') $completed_count++;
                if ($row['due_date'] && $row['due_date'] < $today && $status !== 'completed' && $status !== 'done') {
                    $overdue_count++;
                }
                $rows[] = $row;
            }
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
        $sql = "SELECT e.employee_id, CONCAT(e.first_name, ' ', e.last_name) as full_name,
                       COALESCE(j.title_name, 'N/A') as position, COALESCE(j.department, 'N/A') as department,
                       COALESCE(e.phone, '-') as phone, COALESCE(e.status, 'Active') as status
                FROM workforce_employees e
                LEFT JOIN workforce_job_titles j ON e.job_title_id = j.job_title_id
                ORDER BY e.last_name, e.first_name";
        
        $result = $conn->query($sql);
        $active_count = 0;
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (strtolower($row['status']) === 'active') $active_count++;
                $rows[] = $row;
            }
        }
        
        $summary = [
            'Total Employees' => count($rows),
            'Active' => $active_count,
            'Inactive' => count($rows) - $active_count
        ];
        break;

    case 'attendance-summary':
        $sql = "SELECT a.attendance_id, CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                       a.attendance_date, a.time_in, a.time_out, a.status
                FROM workforce_attendance a
                LEFT JOIN workforce_employees e ON a.employee_id = e.employee_id
                ORDER BY a.attendance_date DESC, e.last_name LIMIT 100";
        
        $result = $conn->query($sql);
        $present_count = 0;
        $absent_count = 0;
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Calculate hours worked
                $hours = '-';
                if ($row['time_in'] && $row['time_out']) {
                    $diff = strtotime($row['time_out']) - strtotime($row['time_in']);
                    $hours = round($diff / 3600, 1);
                }
                $row['hours_worked'] = $hours;
                
                $status = strtolower($row['status'] ?? 'present');
                if ($status === 'present' || $status === 'on time' || $status === 'late') {
                    $present_count++;
                } else {
                    $absent_count++;
                }
                
                $rows[] = $row;
            }
        }
        
        $summary = [
            'Total Records' => count($rows),
            'Present' => $present_count,
            'Absent/Leave' => $absent_count
        ];
        break;

    case 'payroll-report':
        $sql = "SELECT p.payroll_id, CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                       pp.start_date, pp.end_date, p.hours_worked, p.gross_pay, 
                       (p.gross_pay - p.net_pay) as deductions, p.net_pay, p.status
                FROM workforce_payroll p
                LEFT JOIN workforce_employees e ON p.employee_id = e.employee_id
                LEFT JOIN workforce_payroll_periods pp ON p.period_id = pp.period_id
                ORDER BY pp.end_date DESC, e.last_name LIMIT 100";
        
        $result = $conn->query($sql);
        $total_gross = 0;
        $total_deductions = 0;
        $total_net = 0;
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $total_gross += floatval($row['gross_pay']);
                $total_deductions += floatval($row['deductions']);
                $total_net += floatval($row['net_pay']);
                
                // Format period
                $row['period'] = date('M j', strtotime($row['start_date'])) . ' - ' . date('M j, Y', strtotime($row['end_date']));
                $rows[] = $row;
            }
        }
        
        $summary = [
            'Total Gross Pay' => '₱' . number_format($total_gross, 2),
            'Total Deductions' => '₱' . number_format($total_deductions, 2),
            'Total Net Pay' => '₱' . number_format($total_net, 2)
        ];
        break;
}

// Log the report generation
$tableExists = $conn->query("SHOW TABLES LIKE 'icmis_generated_reports'");
if ($tableExists && $tableExists->num_rows > 0) {
    $stmt = $conn->prepare("INSERT INTO icmis_generated_reports (report_name, category, project_id, project_name, generated_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $category = explode('-', $report_type)[0];
    $stmt->bind_param("ssiss", $report_title, $category, $project_id, $project_name, $userName);
    $stmt->execute();
    $stmt->close();
}

// Also try the old table name
$tableExists = $conn->query("SHOW TABLES LIKE 'budget_generated_reports'");
if ($tableExists && $tableExists->num_rows > 0) {
    // Ensure project_id of 0 is stored as NULL to satisfy FK (use NULLIF to convert 0->NULL)
    $stmt = $conn->prepare("INSERT INTO budget_generated_reports (report_type, report_name, project_id, generated_by, created_at) VALUES (?, ?, NULLIF(?,0), ?, NOW())");
    $category = explode('-', $report_type)[0];
    $stmt->bind_param("ssis", $category, $report_title, $project_id, $userName);
    $stmt->execute();
    $stmt->close();
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

// Log the report generation
Logger::init($conn);
Logger::export('Reports', "Generated Report: $report_title" . ($project_id > 0 ? " for $project_name" : ""), $project_id > 0 ? $project_id : null);

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
