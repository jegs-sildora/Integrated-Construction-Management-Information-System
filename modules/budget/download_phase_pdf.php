<?php
// download_phase_pdf.php
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
$allocated = 0;
$spent = 0;
$remaining = 0;
$utilization = 0;
$status = 'Active';
$date_range = 'No dates set';

if ($result_phase->num_rows > 0) {
    $phase_data = $result_phase->fetch_assoc();
    $allocated = floatval($phase_data['allocated']);

    // Format date range
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
    
    if ($result_expenses->num_rows > 0) {
        $expense_data = $result_expenses->fetch_assoc();
        $spent = floatval($expense_data['spent']);
    }

    $remaining = $allocated - $spent;
    $utilization = $allocated > 0 ? ($spent / $allocated) * 100 : 0;

    // Determine status
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

// Helper for status badge color
$statusColor = match($status) {
    'Completed' => 'text-green-700 bg-green-50 border-green-200',
    'Active' => 'text-blue-700 bg-blue-50 border-blue-200',
    'Over Budget' => 'text-red-700 bg-red-50 border-red-200',
    default => 'text-gray-700 bg-gray-50 border-gray-200',
};

// Helper for utilization color
$progressColor = 'bg-green-500';
if ($utilization > 100) {
    $progressColor = 'bg-red-500';
} elseif ($utilization >= 90) {
    $progressColor = 'bg-amber-500';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phase Budget - <?php echo htmlspecialchars($phase_name); ?></title>
    
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
        
        <div class="text-center border-b-2 border-slate-800 pb-6 mb-8">
            <img src="../../assets/images/nobg_logo.png" alt="ICMIS Logo" class="print-logo">
            
            <h1 class="text-2xl font-black uppercase tracking-wide text-slate-900 mt-2">Phase Budget Report</h1>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-widest">Integrated Construction Management Information System</p>
            <p class="text-xs text-slate-400 mt-1">Generated on: <?php echo date('F j, Y h:i A'); ?></p>
        </div>

        <div class="grid grid-cols-2 gap-8 mb-8 text-sm">
            <div>
                <table class="w-full">
                    <tr>
                        <td class="font-bold text-slate-500 py-1 w-32">Project:</td>
                        <td class="font-bold text-slate-900 py-1"><?php echo htmlspecialchars($project['project_name']); ?></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-500 py-1">Project Code:</td>
                        <td class="font-mono font-bold text-slate-700 py-1"><?php echo htmlspecialchars($project['project_code']); ?></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-500 py-1">Phase:</td>
                        <td class="font-bold text-slate-900 py-1 text-lg"><?php echo htmlspecialchars($phase_name); ?></td>
                    </tr>
                </table>
            </div>
            <div>
                <table class="w-full">
                    <tr>
                        <td class="font-bold text-slate-500 py-1 w-32">Date Range:</td>
                        <td class="text-slate-900 py-1"><?php echo htmlspecialchars($date_range); ?></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-500 py-1">Status:</td>
                        <td class="py-1">
                            <span class="px-2 py-0.5 rounded border text-xs font-bold uppercase <?php echo $statusColor; ?>">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-500 py-1">Utilization:</td>
                        <td class="text-slate-700 py-1 font-mono"><?php echo number_format($utilization, 1); ?>%</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="mb-8 bg-slate-50 rounded-lg border border-slate-200 p-6">
            <h3 class="text-xs font-bold text-slate-500 uppercase mb-4">Financial Overview</h3>
            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="p-2 border-r border-slate-200">
                    <p class="text-xs text-slate-500 uppercase font-bold">Allocated Budget</p>
                    <p class="text-xl font-black text-slate-800 mt-1 font-mono">₱<?php echo number_format($allocated, 2); ?></p>
                </div>
                <div class="p-2 border-r border-slate-200">
                    <p class="text-xs text-slate-500 uppercase font-bold">Total Spent</p>
                    <p class="text-xl font-black text-blue-700 mt-1 font-mono">₱<?php echo number_format($spent, 2); ?></p>
                </div>
                <div class="p-2">
                    <p class="text-xs text-slate-500 uppercase font-bold">Remaining</p>
                    <p class="text-xl font-black text-slate-800 mt-1 font-mono">₱<?php echo number_format($remaining, 2); ?></p>
                </div>
            </div>
            
            <div class="mt-4 w-full bg-slate-200 rounded-full h-2.5">
                <div class="<?php echo $progressColor; ?> h-2.5 rounded-full" style="width: <?php echo min($utilization, 100); ?>%"></div>
            </div>
        </div>

        <div class="mb-8">
            <h3 class="text-sm font-bold text-slate-900 uppercase border-b border-slate-200 pb-2 mb-4">Approved Budget Proposals</h3>
            <?php if (count($proposals) > 0): ?>
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Code</th>
                        <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Title</th>
                        <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Created By</th>
                        <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-center">Date</th>
                        <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proposals as $proposal): ?>
                    <tr>
                        <td class="px-4 py-2 text-sm border border-slate-200 text-slate-800 font-mono"><?php echo htmlspecialchars($proposal['code']); ?></td>
                        <td class="px-4 py-2 text-sm border border-slate-200 text-slate-700 font-medium"><?php echo htmlspecialchars($proposal['title']); ?></td>
                        <td class="px-4 py-2 text-sm border border-slate-200 text-slate-600"><?php echo htmlspecialchars($proposal['user_name']); ?></td>
                        <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-600"><?php echo date('M j, Y', strtotime($proposal['created_at'])); ?></td>
                        <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono font-bold text-slate-900">₱<?php echo number_format($proposal['total_amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="px-4 py-8 text-center text-slate-500 italic border border-slate-200 bg-slate-50">No approved budget proposals found for this phase.</div>
            <?php endif; ?>
        </div>

        <div class="mb-8">
            <h3 class="text-sm font-bold text-slate-900 uppercase border-b border-slate-200 pb-2 mb-4">Expense Line Items</h3>
            <?php if (count($expenses) > 0): ?>
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-center">Date</th>
                        <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Category</th>
                        <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Description</th>
                        <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Supplier</th>
                        <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-600"><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></td>
                        <td class="px-4 py-2 text-sm border border-slate-200 text-slate-700">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                <?php echo htmlspecialchars($expense['category']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-2 text-sm border border-slate-200 text-slate-700"><?php echo htmlspecialchars($expense['description']); ?></td>
                        <td class="px-4 py-2 text-sm border border-slate-200 text-slate-600 italic"><?php echo htmlspecialchars($expense['supplier_name'] ?: 'N/A'); ?></td>
                        <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono font-bold text-slate-900">₱<?php echo number_format($expense['amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <tr class="bg-slate-50">
                        <td colspan="4" class="px-4 py-3 text-right text-sm font-black uppercase text-slate-600 border border-slate-300">Total Phase Expenses:</td>
                        <td class="px-4 py-3 text-right text-lg font-black text-slate-900 border border-slate-300 font-mono">₱<?php echo number_format($spent, 2); ?></td>
                    </tr>
                </tbody>
            </table>
            <?php else: ?>
            <div class="px-4 py-8 text-center text-slate-500 italic border border-slate-200 bg-slate-50">No expenses recorded for this phase yet.</div>
            <?php endif; ?>
        </div>

        <div class="print-footer mt-12 pt-8">
            <div class="grid grid-cols-3 gap-8">
                <div class="text-center">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-12">Prepared By:</p>
                    <div class="border-b border-slate-800 w-3/4 mx-auto"></div>
                    <p class="text-sm font-bold mt-2 text-slate-900 uppercase">System Generated</p>
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

        <div class="no-print mt-8 text-center">
            <p class="text-sm text-gray-500 mb-4">Press the button below if printing does not start automatically.</p>
            <button onclick="window.print()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold shadow-md transition-colors">
                Print Report
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