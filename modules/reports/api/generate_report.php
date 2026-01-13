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

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8mb4");

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$template = $input['template'] ?? '';
$project_id = intval($input['project_id'] ?? 0);

if (empty($template)) {
    echo json_encode(['success' => false, 'message' => 'Template type is required']);
    exit;
}

// Get project info if specified
$project = null;
if ($project_id > 0) {
    $stmt = $conn->prepare("SELECT p.project_id, p.project_name, p.project_code, p.location, p.total_budget,
                                   COALESCE((SELECT SUM(e.amount) FROM budget_expenses e WHERE e.project_id = p.project_id AND e.status = 'APPROVED'), 0) as actual_spending
                            FROM icmis_projects p WHERE p.project_id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $project = [
            'id' => $row['project_id'],
            'name' => $row['project_name'],
            'code' => $row['project_code'],
            'location' => $row['location'],
            'total_budget' => floatval($row['total_budget']),
            'actual_spending' => floatval($row['actual_spending'])
        ];
    }
    $stmt->close();
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
            
            $sql = "SELECT 
                        category,
                        SUM(amount) as total_spent
                    FROM budget_expenses 
                    WHERE 1=1";
            if ($project_id > 0) {
                $sql .= " AND project_id = $project_id";
            }
            $sql .= " GROUP BY category ORDER BY category";
            
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $spent = floatval($row['total_spent']);
                    $allocated = $spent * 1.2; // Placeholder - ideally from budget_proposals
                    $remaining = $allocated - $spent;
                    $utilization = $allocated > 0 ? round(($spent / $allocated) * 100, 1) : 0;
                    
                    $data['rows'][] = [
                        $row['category'],
                        'PHP ' . number_format($allocated, 2),
                        'PHP ' . number_format($spent, 2),
                        'PHP ' . number_format($remaining, 2),
                        $utilization . '%'
                    ];
                }
            }
            break;

        case 'expense-log':
            $data['headers'] = ['Date', 'Category', 'Description', 'Supplier', 'Amount'];
            
            $sql = "SELECT e.expense_date, e.category, e.description, s.supplier_name, e.amount 
                    FROM budget_expenses e
                    LEFT JOIN procurement_suppliers s ON e.supplier_id = s.supplier_id
                    WHERE 1=1";
            if ($project_id > 0) {
                $sql .= " AND e.project_id = $project_id";
            }
            $sql .= " ORDER BY e.expense_date DESC LIMIT 100";
            
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $data['rows'][] = [
                        date('M j, Y', strtotime($row['expense_date'])),
                        $row['category'],
                        $row['description'],
                        $row['supplier_name'] ?: '-',
                        'PHP ' . number_format($row['amount'], 2)
                    ];
                }
            }
            break;

        case 'cash-flow':
            $data['headers'] = ['Month', 'Inflow', 'Outflow', 'Net Cash Flow'];
            
            $sql = "SELECT 
                        DATE_FORMAT(expense_date, '%Y-%m') as month_year,
                        SUM(amount) as monthly_total
                    FROM budget_expenses 
                    WHERE 1=1";
            if ($project_id > 0) {
                $sql .= " AND project_id = $project_id";
            }
            $sql .= " GROUP BY DATE_FORMAT(expense_date, '%Y-%m') ORDER BY month_year DESC LIMIT 12";
            
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $outflow = floatval($row['monthly_total']);
                    $inflow = $outflow * 1.1; // Placeholder
                    $net = $inflow - $outflow;
                    
                    $dateParts = explode('-', $row['month_year']);
                    $monthName = date('F Y', mktime(0, 0, 0, $dateParts[1], 1, $dateParts[0]));
                    
                    $data['rows'][] = [
                        $monthName,
                        'PHP ' . number_format($inflow, 2),
                        'PHP ' . number_format($outflow, 2),
                        'PHP ' . number_format($net, 2)
                    ];
                }
            }
            break;

        // ========================================
        // PROCUREMENT REPORTS
        // ========================================
        case 'inventory-status':
            $data['headers'] = ['Item ID', 'Item Name', 'Category', 'In Stock', 'Unit', 'Status'];
            
            $sql = "SELECT item_id, item_name, category, quantity, unit 
                    FROM procurement_inventory 
                    ORDER BY item_name";
            
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $qty = intval($row['quantity']);
                    $status = $qty <= 10 ? 'Low Stock' : ($qty <= 50 ? 'Normal' : 'Well Stocked');
                    
                    $data['rows'][] = [
                        $row['item_id'],
                        $row['item_name'],
                        $row['category'],
                        $qty,
                        $row['unit'],
                        $status
                    ];
                }
            }
            break;

        case 'purchase-orders':
            $data['headers'] = ['PO Number', 'Supplier', 'Date', 'Total Amount', 'Status'];
            
            $sql = "SELECT po.po_reference, s.supplier_name, po.order_date, po.total_amount, po.status 
                    FROM procurement_purchase_orders po
                    LEFT JOIN procurement_suppliers s ON po.supplier_id = s.supplier_id
                    ORDER BY po.order_date DESC LIMIT 50";
            
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $data['rows'][] = [
                        $row['po_reference'],
                        $row['supplier_name'] ?: 'N/A',
                        date('M j, Y', strtotime($row['order_date'])),
                        'PHP ' . number_format($row['total_amount'], 2),
                        ucfirst($row['status'])
                    ];
                }
            }
            break;

        case 'stock-movement':
            $data['headers'] = ['Date', 'Item', 'Type', 'Quantity', 'PO Reference', 'Issued To/From'];
            
            // Try stock_in table first
            $movements = [];
            
                 $sql = "SELECT si.date_received as movement_date, COALESCE(i.item_name, '') as item_name, 'Stock In' as type, 
                          si.quantity_received as quantity, po.po_reference, 'Supplier' as handler
                      FROM procurement_stock_in si
                      LEFT JOIN procurement_purchase_orders po ON si.po_id = po.po_id
                      LEFT JOIN procurement_inventory i ON si.item_id = i.item_id
                      ORDER BY si.date_received DESC LIMIT 25";
            
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $movements[] = $row;
                }
            }
            
                 $sql = "SELECT so.date_issued as movement_date, i.item_name, 'Stock Out' as type,
                          COALESCE(so.quantity, so.qty, so.quantity_issued, 0) as quantity, NULL as po_reference, CONCAT(emp.first_name, ' ', emp.last_name) as handler
                      FROM procurement_stock_out so
                      LEFT JOIN procurement_inventory i ON so.item_id = i.item_id
                      LEFT JOIN workforce_employees emp ON so.issued_to_employee_id = emp.employee_id
                      ORDER BY so.date_issued DESC LIMIT 25";
            
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $movements[] = $row;
                }
            }
            
            // Sort by date
            usort($movements, function($a, $b) {
                return strtotime($b['movement_date']) - strtotime($a['movement_date']);
            });
            
            foreach (array_slice($movements, 0, 50) as $row) {
                $data['rows'][] = [
                    date('M j, Y', strtotime($row['movement_date'])),
                    $row['item_name'] ?: 'N/A',
                    $row['type'],
                    $row['quantity'],
                    $row['po_reference'] ?: '-',
                    $row['handler'] ?? '-'
                ];
            }
            break;

        // ========================================
        // PROJECT REPORTS
        // ========================================
        case 'project-summary':
            $data['headers'] = ['Project', 'Code', 'Location', 'Budget', 'Status'];
            
            $sql = "SELECT project_name, project_code, location, total_budget, status 
                    FROM icmis_projects";
            if ($project_id > 0) {
                $sql .= " WHERE project_id = $project_id";
            }
            $sql .= " ORDER BY project_id DESC";
            
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $data['rows'][] = [
                        $row['project_name'],
                        $row['project_code'],
                        $row['location'] ?: 'N/A',
                        'PHP ' . number_format($row['total_budget'], 2),
                        ucfirst($row['status'] ?? 'Active')
                    ];
                }
            }
            break;

        case 'phase-progress':
            $data['headers'] = ['Phase', 'Project', 'Start Date', 'End Date', 'Duration', 'Status'];
            
            $sql = "SELECT pp.phase_name, p.project_name, pp.start_date, pp.end_date, pp.duration, pp.status 
                    FROM icmis_project_phases pp
                    LEFT JOIN icmis_projects p ON pp.project_id = p.project_id
                    WHERE 1=1";
            if ($project_id > 0) {
                $sql .= " AND pp.project_id = $project_id";
            }
            $sql .= " ORDER BY pp.start_date";
            
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $data['rows'][] = [
                        $row['phase_name'],
                        $row['project_name'],
                        $row['start_date'] ? date('M j, Y', strtotime($row['start_date'])) : '-',
                        $row['end_date'] ? date('M j, Y', strtotime($row['end_date'])) : '-',
                        ($row['duration'] ?? 0) . ' days',
                        ucfirst($row['status'] ?? 'Not Started')
                    ];
                }
            }
            break;

        case 'task-status':
            $data['headers'] = ['Task', 'Phase', 'Assignee', 'Due Date', 'Priority', 'Status'];
            
            $sql = "SELECT t.task_name, pp.phase_name, 
                           CONCAT(e.first_name, ' ', e.last_name) as assigned_to, 
                           t.due_date, t.priority, t.status 
                    FROM icmis_tasks t
                    LEFT JOIN icmis_project_phases pp ON t.phase_id = pp.phase_id
                    LEFT JOIN workforce_employees e ON t.assigned_to_employee_id = e.employee_id
                    WHERE 1=1";
            if ($project_id > 0) {
                $sql .= " AND t.project_id = $project_id";
            }
            $sql .= " ORDER BY t.due_date LIMIT 100";
            
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $data['rows'][] = [
                        $row['task_name'],
                        $row['phase_name'] ?: 'N/A',
                        $row['assigned_to'] ?: 'Unassigned',
                        $row['due_date'] ? date('M j, Y', strtotime($row['due_date'])) : '-',
                        ucfirst($row['priority'] ?? 'Normal'),
                        ucfirst($row['status'] ?? 'Pending')
                    ];
                }
            }
            break;

        // ========================================
        // WORKFORCE REPORTS
        // ========================================
        case 'employee-roster':
            $data['headers'] = ['Employee ID', 'Name', 'Position', 'Department', 'Contact', 'Status'];
            
            $sql = "SELECT e.employee_id, CONCAT(e.first_name, ' ', e.last_name) as full_name, 
                           j.title_name as position, j.department, e.phone, e.status 
                    FROM workforce_employees e
                    LEFT JOIN workforce_job_titles j ON e.job_title_id = j.job_title_id
                    ORDER BY e.last_name, e.first_name";
            
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $data['rows'][] = [
                        $row['employee_id'],
                        $row['full_name'],
                        $row['position'] ?: 'N/A',
                        $row['department'] ?: 'N/A',
                        $row['phone'] ?: '-',
                        ucfirst($row['status'] ?? 'Active')
                    ];
                }
            }
            break;

        case 'attendance-summary':
            $data['headers'] = ['Employee', 'Date', 'Time In', 'Time Out', 'Hours Worked', 'Status'];
            
            $sql = "SELECT e.first_name, e.last_name, a.attendance_date, a.time_in, a.time_out, a.status 
                    FROM workforce_attendance a
                    LEFT JOIN workforce_employees e ON a.employee_id = e.employee_id
                    ORDER BY a.attendance_date DESC, e.last_name LIMIT 100";
            
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $hours = '-';
                    if ($row['time_in'] && $row['time_out']) {
                        $diff = strtotime($row['time_out']) - strtotime($row['time_in']);
                        $hours = round($diff / 3600, 1) . ' hrs';
                    }
                    
                    $data['rows'][] = [
                        $row['first_name'] . ' ' . $row['last_name'],
                        date('M j, Y', strtotime($row['attendance_date'])),
                        $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '-',
                        $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '-',
                        $hours,
                        ucfirst($row['status'] ?? 'Present')
                    ];
                }
            }
            break;

        case 'payroll-report':
            $data['headers'] = ['Employee', 'Period', 'Hours Worked', 'Gross Pay', 'Net Pay', 'Status'];
            
                 $sql = "SELECT e.first_name, e.last_name, NULL as period_name, pp.start_date, pp.end_date,
                          p.hours_worked, p.gross_pay, p.net_pay, p.status 
                      FROM workforce_payroll p
                      LEFT JOIN workforce_employees e ON p.employee_id = e.employee_id
                      LEFT JOIN workforce_payroll_periods pp ON p.period_id = pp.period_id
                      ORDER BY pp.end_date DESC, e.last_name LIMIT 100";
            
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $period = $row['period_name'] ?: (date('M j', strtotime($row['start_date'])) . ' - ' . date('M j, Y', strtotime($row['end_date'])));
                    $deductions = floatval($row['gross_pay']) - floatval($row['net_pay']);
                    
                    $data['rows'][] = [
                        $row['first_name'] . ' ' . $row['last_name'],
                        $period,
                        $row['hours_worked'] . ' hrs',
                        'PHP ' . number_format($row['gross_pay'], 2),
                        'PHP ' . number_format($row['net_pay'], 2),
                        ucfirst($row['status'] ?? 'Calculated')
                    ];
                }
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown template type: ' . $template]);
            exit;
    }

    // Log the report generation (if table exists)
    $userName = $_SESSION['user_name'] ?? 'Admin';
    $tableExists = $conn->query("SHOW TABLES LIKE 'budget_generated_reports'");
    if ($tableExists && $tableExists->num_rows > 0) {
        $category = explode('-', $template)[0];
        $reportName = ucwords(str_replace('-', ' ', $template));
        
        // Convert project_id=0 into NULL so FK to icmis_projects is not violated
        $stmt = $conn->prepare("INSERT INTO budget_generated_reports (report_type, report_name, project_id, generated_by, created_at) VALUES (?, ?, NULLIF(?,0), ?, NOW())");
        $stmt->bind_param("ssis", $category, $reportName, $project_id, $userName);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error generating report: ' . $e->getMessage()]);
}

$conn->close();
?>
