<?php
// Include config for database connection
require_once __DIR__ . '/../../config/config.php';

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

if (!isset($_GET['project_id']) || !isset($_GET['phase']) || empty($_GET['project_id']) || empty($_GET['phase'])) {
    die('Project ID and Phase are required');
}

$project_id = intval($_GET['project_id']);
$phase_name = urldecode($_GET['phase']);

// Fetch project details
$sql_project = "SELECT project_id, project_code, project_name FROM icmis_projects WHERE project_id = ?";
$stmt_project = $conn->prepare($sql_project);
$stmt_project->bind_param("i", $project_id);
$stmt_project->execute();
$result_project = $stmt_project->get_result();

if ($result_project->num_rows === 0) {
    die('Project not found');
}

$project = $result_project->fetch_assoc();

// Fetch phase data
$sql_phase = "SELECT
                pp.phase_name as phase,
                COALESCE(SUM(bp.total_amount), 0) as allocated,
                MIN(pp.start_date) as phase_start_date,
                MAX(pp.end_date) as phase_end_date
             FROM budget_proposals bp
             LEFT JOIN icmis_project_phases pp ON bp.phase_id = pp.phase_id
             WHERE bp.project_id = ? AND bp.status = 'APPROVED' AND pp.phase_name = ?
             GROUP BY pp.phase_id";
$stmt_phase = $conn->prepare($sql_phase);
$stmt_phase->bind_param("is", $project_id, $phase_name);
$stmt_phase->execute();
$result_phase = $stmt_phase->get_result();

$phase_data = null;
if ($result_phase->num_rows > 0) {
    $phase_data = $result_phase->fetch_assoc();
    $allocated = floatval($phase_data['allocated']);

    // Format date range
    $date_range = 'No dates set';
    if (!empty($phase_data['phase_start_date']) && !empty($phase_data['phase_end_date'])) {
        $start_date = new DateTime($phase_data['phase_start_date']);
        $end_date = new DateTime($phase_data['phase_end_date']);
        $date_range = $start_date->format('M j') . ' - ' . $end_date->format('M j, Y');
    }

    // Get expenses for this phase
    $sql_expenses = "SELECT COALESCE(SUM(CASE WHEN e.status = 'APPROVED' THEN e.amount ELSE 0 END), 0) as spent 
                     FROM budget_expenses e
                     LEFT JOIN icmis_project_phases pp ON e.phase_id = pp.phase_id
                     WHERE e.project_id = ? AND pp.phase_name = ?";
    $stmt_expenses = $conn->prepare($sql_expenses);
    $stmt_expenses->bind_param("is", $project_id, $phase_name);
    $stmt_expenses->execute();
    $result_expenses = $stmt_expenses->get_result();
    $spent = 0;
    if ($result_expenses->num_rows > 0) {
        $expense_data = $result_expenses->fetch_assoc();
        $spent = floatval($expense_data['spent']);
    }

    $remaining = $allocated - $spent;
    $utilization = $allocated > 0 ? ($spent / $allocated) * 100 : 0;

    // Determine status
    $status = 'Active';
    if ($utilization > 100) {
        $status = 'Over Budget';
    } elseif ($utilization >= 99) {
        $status = 'Completed';
    }
}

// Fetch budget proposals for this phase with user info
$sql_proposals = "SELECT bp.*, COALESCE(u.full_name, 'System') as user_name
                  FROM budget_proposals bp
                  LEFT JOIN icmis_project_phases pp ON bp.phase_id = pp.phase_id
                  LEFT JOIN icmis_users u ON bp.created_by = u.user_id
                  WHERE bp.project_id = ? AND pp.phase_name = ? AND bp.status = 'APPROVED'
                  ORDER BY bp.created_at DESC";
$stmt_proposals = $conn->prepare($sql_proposals);
$stmt_proposals->bind_param("is", $project_id, $phase_name);
$stmt_proposals->execute();
$result_proposals = $stmt_proposals->get_result();

$proposals = [];
while ($row = $result_proposals->fetch_assoc()) {
    $proposals[] = $row;
}

// Fetch expenses for this phase
$sql_expenses_list = "SELECT e.*, s.supplier_name
                      FROM budget_expenses e
                      LEFT JOIN procurement_suppliers s ON e.supplier_id = s.supplier_id
                      LEFT JOIN icmis_project_phases pp ON e.phase_id = pp.phase_id
                      WHERE e.project_id = ? AND pp.phase_name = ?
                      ORDER BY e.expense_date DESC";
$stmt_expenses_list = $conn->prepare($sql_expenses_list);
$stmt_expenses_list->bind_param("is", $project_id, $phase_name);
$stmt_expenses_list->execute();
$result_expenses_list = $stmt_expenses_list->get_result();

$expenses = [];
while ($row = $result_expenses_list->fetch_assoc()) {
    $expenses[] = $row;
}

if (!$phase_data) {
    die('Phase data not found');
}

?>
<!DOCTYPE html>
<html>
<head>
    <!-- Global project styles -->
    <link rel="stylesheet" href="/icmis_budget/css/output.css">
    <link rel="stylesheet" href="/icmis_budget/css/input.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phase Budget Details - <?php echo htmlspecialchars($phase_name); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Arial', sans-serif;
            padding: 40px;
            background: white;
            color: #333;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #e9922c;
        }
        .logo-container {
            display: inline-block;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e9922c, #d17f1f);
            border-radius: 12px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            color: white;
        }
        .header h1 {
            font-size: 32px;
            color: #1f2937;
            margin-bottom: 5px;
        }
        .header .subtitle {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .phase-title {
            display: inline-block;
            background: linear-gradient(135deg, #e9922c, #d17f1f);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 16px;
            margin-top: 10px;
        }
        .info-section {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table tr {
            border-bottom: 1px solid #e5e7eb;
        }
        .info-table tr:last-child {
            border-bottom: none;
        }
        .info-table td {
            padding: 12px 15px;
        }
        .info-table td:first-child {
            font-weight: bold;
            color: #4b5563;
            width: 30%;
        }
        .info-table td:last-child {
            color: #1f2937;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-active { background: #3b82f6; color: white; }
        .status-completed { background: #10b981; color: white; }
        .status-over-budget { background: #ef4444; color: white; }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        .summary-card.allocated { border-color: #10b981; }
        .summary-card.spent { border-color: #3b82f6; }
        .summary-card.remaining { border-color: #8b5cf6; }
        .summary-card .label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .summary-card .amount {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
        }
        .progress-section {
            margin-bottom: 30px;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 10px;
        }
        .progress-fill.warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .progress-fill.danger { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            margin: 30px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9922c;
        }
        .proposals-table, .expenses-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .proposals-table th, .expenses-table th {
            background: linear-gradient(135deg, #e9922c, #d17f1f);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            font-size: 14px;
        }
        .proposals-table td, .expenses-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .proposals-table tr:hover, .expenses-table tr:hover {
            background: #f9fafb;
        }
        .category-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            color: white;
        }
        .category-material { background: #8b5cf6; }
        .category-labor { background: #f59e0b; }
        .category-equipment { background: #10b981; }
        .status-approved { background: #10b981; color: white; }
        .status-pending { background: #f59e0b; color: white; }
        .total-section {
            background: linear-gradient(135deg, #e9922c, #d17f1f);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-top: 30px;
            text-align: right;
        }
        .total-label {
            font-size: 14px;
            margin-bottom: 5px;
            opacity: 0.9;
        }
        .total-amount {
            font-size: 36px;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-style: italic;
        }
        @media print {
            body { padding: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div style="text-align: center;">
            <div class="logo-container">I</div>
        </div>
        <h1>ICMIS</h1>
        <p class="subtitle">Integrated Construction Management Information System</p>
        <span class="phase-title"><?php echo htmlspecialchars($phase_name); ?> - Budget Details</span>
    </div>

    <div class="info-section">
        <table class="info-table">
            <tr>
                <td>Project:</td>
                <td><?php echo htmlspecialchars($project['name']); ?> (<?php echo htmlspecialchars($project['project_code']); ?>)</td>
            </tr>
            <tr>
                <td>Phase:</td>
                <td><?php echo htmlspecialchars($phase_name); ?></td>
            </tr>
            <tr>
                <td>Date Range:</td>
                <td><?php echo htmlspecialchars($date_range); ?></td>
            </tr>
            <tr>
                <td>Status:</td>
                <td>
                    <?php
                    $statusClass = '';
                    switch($status) {
                        case 'Completed': $statusClass = 'status-completed'; break;
                        case 'Over Budget': $statusClass = 'status-over-budget'; break;
                        default: $statusClass = 'status-active';
                    }
                    ?>
                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                </td>
            </tr>
            <tr>
                <td>Report Generated:</td>
                <td><?php echo date('F j, Y g:i A'); ?></td>
            </tr>
        </table>
    </div>

    <div class="summary-cards">
        <div class="summary-card allocated">
            <div class="label">Allocated Budget</div>
            <div class="amount">₱<?php echo number_format($allocated, 2); ?></div>
        </div>
        <div class="summary-card spent">
            <div class="label">Total Spent</div>
            <div class="amount">₱<?php echo number_format($spent, 2); ?></div>
            <div style="font-size: 12px; color: #6b7280; margin-top: 4px;"><?php echo number_format($utilization, 1); ?>% utilized</div>
        </div>
        <div class="summary-card remaining">
            <div class="label">Remaining Budget</div>
            <div class="amount">₱<?php echo number_format($remaining, 2); ?></div>
        </div>
    </div>

    <div class="progress-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <span style="font-weight: bold; color: #1f2937;">Budget Utilization Progress</span>
            <span style="font-weight: bold; color: #1f2937;"><?php echo number_format($utilization, 1); ?>%</span>
        </div>
        <div class="progress-bar">
            <?php
            $progressClass = 'progress-fill';
            if ($utilization > 100) {
                $progressClass .= ' danger';
            } elseif ($utilization >= 90) {
                $progressClass .= ' warning';
            }
            ?>
            <div class="<?php echo $progressClass; ?>" style="width: <?php echo min($utilization, 100); ?>%"></div>
        </div>
    </div>

    <h2 class="section-title">Approved Budget Proposals</h2>
    <?php if (count($proposals) > 0): ?>
    <table class="proposals-table">
        <thead>
            <tr>
                <th style="width: 15%;">Proposal Code</th>
                <th style="width: 30%;">Title</th>
                <th style="width: 20%;">Created By</th>
                <th style="width: 15%;">Date</th>
                <th style="width: 20%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($proposals as $proposal): ?>
            <tr>
                <td><?php echo htmlspecialchars($proposal['code']); ?></td>
                <td><?php echo htmlspecialchars($proposal['title']); ?></td>
                <td><?php echo htmlspecialchars($proposal['user_name']); ?></td>
                <td><?php echo date('M j, Y', strtotime($proposal['created_at'])); ?></td>
                <td style="text-align: right; font-weight: bold;">₱<?php echo number_format($proposal['total_amount'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="no-data">No approved budget proposals found for this phase.</div>
    <?php endif; ?>

    <h2 class="section-title">Expense Line Items</h2>
    <?php if (count($expenses) > 0): ?>
    <table class="expenses-table">
        <thead>
            <tr>
                <th style="width: 15%;">Date</th>
                <th style="width: 20%;">Category</th>
                <th style="width: 35%;">Description</th>
                <th style="width: 15%;">Supplier</th>
                <th style="width: 15%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($expenses as $expense): ?>
            <tr>
                <td><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></td>
                <td>
                    <?php
                    $catClass = '';
                    switch($expense['category']) {
                        case 'MATERIALS': $catClass = 'category-material'; break;
                        case 'LABOR': $catClass = 'category-labor'; break;
                        case 'EQUIPMENT': $catClass = 'category-equipment'; break;
                    }
                    ?>
                    <span class="category-badge <?php echo $catClass; ?>"><?php echo htmlspecialchars($expense['category']); ?></span>
                </td>
                <td><?php echo htmlspecialchars($expense['description']); ?></td>
                <td><?php echo htmlspecialchars($expense['supplier_name'] ?: 'N/A'); ?></td>
                <td style="text-align: right; font-weight: bold;">₱<?php echo number_format($expense['amount'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="no-data">No expenses recorded for this phase yet.</div>
    <?php endif; ?>

    <div class="total-section">
        <div class="total-label">Phase Budget Total (Approved Expenses)</div>
        <div class="total-amount">₱<?php echo number_format($spent, 2); ?></div>
    </div>

    <div class="footer">
        <p>This is a computer-generated document. No signature required.</p>
        <p>Generated on <?php echo date('F j, Y g:i A'); ?> | ICMIS Budget Management System</p>
    </div>

    <script>
        // Auto-print when page loads (for PDF generation)
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
<?php
$conn->close();
?>