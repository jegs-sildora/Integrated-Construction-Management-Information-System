<?php
// generate_report.php - server-side printable report generator
require_once __DIR__ . '/project_context.php';

// Get params
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'expense-log';
$phase = isset($_GET['phase']) ? $_GET['phase'] : '';

if ($project_id <= 0) {
    echo "<h2>Project ID is required</h2>";
    exit;
}

// Call export_pdf.php via HTTP POST to retrieve JSON data (avoid include to prevent redeclare errors)
$exportPath = dirname($_SERVER['REQUEST_URI']) . '/budget_expenses/export_pdf.php';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$exportUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . $exportPath;

$postFields = ['project_id' => $project_id, 'report_type' => $report_type];
if ($phase !== '') $postFields['phase'] = $phase;

$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query($postFields),
        'timeout' => 10
    ]
];

$context  = stream_context_create($options);
$json = @file_get_contents($exportUrl, false, $context);
if ($json === false) {
    // Fallback to cURL if allow_url_fopen is disabled or file_get_contents failed
    if (function_exists('curl_version')) {
        $ch = curl_init($exportUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $json = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($json === false || $json === '') {
            echo "<h2>Error</h2><p>Failed to fetch report data from server via HTTP.</p>";
            echo "<p>cURL error: " . htmlspecialchars($curlErr) . " (HTTP " . intval($httpCode) . ")</p>";
            exit;
        }
    } else {
        echo "<h2>Error</h2><p>Failed to fetch report data from server. Enable allow_url_fopen or cURL on the server.</p>";
        exit;
    }
}

// Some servers may prepend warnings/notices before JSON; strip leading content until first '{'
$firstBrace = strpos($json, '{');
if ($firstBrace !== false && $firstBrace > 0) {
    $json = substr($json, $firstBrace);
}

$data = json_decode($json, true);
if ($data === null) {
    // decoding failed — show diagnostic
    echo "<h2>Error</h2><p>Invalid JSON returned from export endpoint.</p>";
    echo "<pre style=\"white-space:pre-wrap;word-break:break-all;\">" . htmlspecialchars($json) . "</pre>";
    exit;
}
if (!$data || !isset($data['success']) || !$data['success']) {
    $msg = $data['message'] ?? 'Failed to generate report data';
    echo "<h2>Error</h2><p>" . htmlspecialchars($msg) . "</p>";
    exit;
}

$payload = $data['data'];
$project = $payload['project'] ?? [];
// Helper for report type title (define before logging)
$reportTitle = match($report_type) {
    'budget-summary', 'budget_summary' => 'Budget Summary Report',
    'cost-analysis', 'cost_analysis' => 'Cost Analysis Report',
    'phase-analysis' => 'Phase Analysis Report',
    'labor-analysis' => 'Labor Analysis Report',
    'cash-flow' => 'Cash Flow Report',
    'expense-log' => 'Expense Log Report',
    default => ucwords(str_replace(['-', '_'], ' ', $report_type)) . ' Report',
};

// Record generated report in `budget_generated_reports`
try {
    if (function_exists('getBudgetConnection')) {
        $db = getBudgetConnection();
        // Prepare a friendly report name
        $reportName = $reportTitle;
        if (!empty($project['project_name'])) {
            $reportName .= ' - ' . $project['project_name'];
        }

        $generatedBy = $_SESSION['user_name'] ?? 'System';

        // Insert record (created_at uses NOW())
        $ins = $db->prepare("INSERT INTO budget_generated_reports (report_type, report_name, project_id, generated_by, created_at) VALUES (?, ?, NULLIF(?,0), ?, NOW())");
        if ($ins) {
            $pid = isset($project['project_id']) ? intval($project['project_id']) : intval($project_id ?? 0);
            $ins->bind_param('ssis', $report_type, $reportName, $pid, $generatedBy);
            $ins->execute();
            $ins->close();
        }
    }
} catch (Throwable $e) {
    // Do not halt report rendering on logging failure; optionally log to error_log
    error_log('Failed to log generated report: ' . $e->getMessage());
}

// Helper for report type title
$reportTitle = match($report_type) {
    'budget-summary', 'budget_summary' => 'Budget Summary Report',
    'cost-analysis', 'cost_analysis' => 'Cost Analysis Report',
    'phase-analysis' => 'Phase Analysis Report',
    'labor-analysis' => 'Labor Analysis Report',
    'cash-flow' => 'Cash Flow Report',
    'expense-log' => 'Expense Log Report',
    default => ucwords(str_replace(['-', '_'], ' ', $report_type)) . ' Report',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($reportTitle); ?></title>
    
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
            
            <h1 class="text-2xl font-black uppercase tracking-wide text-slate-900 mt-2"><?php echo htmlspecialchars($reportTitle); ?></h1>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-widest">Integrated Construction Management Information System</p>
            <p class="text-xs text-slate-400 mt-1">Generated on: <?php echo date('F j, Y h:i A'); ?></p>
        </div>

        <div class="grid grid-cols-2 gap-8 mb-8 text-sm">
            <div>
                <table class="w-full">
                    <tr>
                        <td class="font-bold text-slate-500 py-1 w-32">Project:</td>
                        <td class="font-bold text-slate-900 py-1"><?php echo htmlspecialchars($project['project_name'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-500 py-1">Project Code:</td>
                        <td class="text-slate-700 py-1"><?php echo htmlspecialchars($project['project_code'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php if ($phase !== ''): ?>
                    <tr>
                        <td class="font-bold text-slate-500 py-1">Phase:</td>
                        <td class="font-mono font-bold text-slate-900 py-1"><?php echo htmlspecialchars($phase); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            <div>
                <table class="w-full">
                    <tr>
                        <td class="font-bold text-slate-500 py-1 w-32">Report Type:</td>
                        <td class="font-bold text-slate-900 py-1"><?php echo htmlspecialchars($reportTitle); ?></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-500 py-1">Generated By:</td>
                        <td class="text-slate-700 py-1"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'System'); ?></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-500 py-1">Date:</td>
                        <td class="text-slate-700 py-1 italic"><?php echo date('F j, Y'); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="mb-8">
            <?php
            // Render template-specific content inside report-container (tables/lists)
            switch ($report_type) {
                case 'phase-analysis':
                    $phases = $payload['phases'] ?? [];
                    ?>
                    <h3 class="text-sm font-bold text-slate-900 uppercase border-b border-slate-200 pb-2 mb-4">Phase Variance Analysis</h3>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-100">
                            <tr>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-center w-12">#</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Phase</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Budget</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Spent</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Variance</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Util.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_budget = 0;
                            $total_spent = 0;
                            if (count($phases) > 0): 
                                foreach ($phases as $index => $p): 
                                    $total_budget += $p['budget'] ?? 0;
                                    $total_spent += $p['spent'] ?? 0;
                            ?>
                            <tr>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-500"><?php echo $index + 1; ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 font-medium text-slate-700"><?php echo htmlspecialchars($p['phase_name'] ?? ''); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono text-slate-700">₱<?php echo number_format($p['budget'] ?? 0, 2); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono text-slate-700">₱<?php echo number_format($p['spent'] ?? 0, 2); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono <?php echo ($p['variance'] ?? 0) < 0 ? 'text-red-600' : 'text-green-600'; ?>">₱<?php echo number_format($p['variance'] ?? 0, 2); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono font-bold text-slate-900"><?php echo number_format($p['utilization'] ?? 0, 1); ?>%</td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500 italic border border-slate-200">No phase data found.</td>
                            </tr>
                            <?php endif; ?>
                            
                            <tr class="bg-slate-50">
                                <td colspan="2" class="px-4 py-3 text-right text-sm font-black uppercase text-slate-600 border border-slate-300">Totals:</td>
                                <td class="px-4 py-3 text-right text-sm font-black text-slate-900 border border-slate-300 font-mono">₱<?php echo number_format($total_budget, 2); ?></td>
                                <td class="px-4 py-3 text-right text-sm font-black text-slate-900 border border-slate-300 font-mono">₱<?php echo number_format($total_spent, 2); ?></td>
                                <td class="px-4 py-3 text-right text-sm font-black border border-slate-300 font-mono <?php echo ($total_budget - $total_spent) < 0 ? 'text-red-600' : 'text-green-600'; ?>">₱<?php echo number_format($total_budget - $total_spent, 2); ?></td>
                                <td class="px-4 py-3 text-right text-lg font-black text-slate-900 border border-slate-300 font-mono"><?php echo $total_budget > 0 ? number_format(($total_spent / $total_budget) * 100, 1) : '0.0'; ?>%</td>
                            </tr>
                        </tbody>
                    </table>
                    <?php
                    break;

                case 'labor-analysis':
                    $list = $payload['labor_expenses'] ?? [];
                    $total = $payload['total_labor'] ?? 0;
                    ?>
                    <h3 class="text-sm font-bold text-slate-900 uppercase border-b border-slate-200 pb-2 mb-4">Labor Expenses Breakdown</h3>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-100">
                            <tr>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-center w-12">#</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Date</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Description</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Phase</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand_total = 0;
                            if (count($list) > 0): 
                                foreach ($list as $index => $row): 
                                    $grand_total += $row['amount'] ?? 0;
                            ?>
                            <tr>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-500"><?php echo $index + 1; ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 font-medium text-slate-700"><?php echo htmlspecialchars(date('M j, Y', strtotime($row['expense_date'] ?? ''))); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-slate-800"><?php echo htmlspecialchars($row['description'] ?? ''); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-slate-700"><?php echo htmlspecialchars($row['phase'] ?? ''); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono font-bold text-slate-900">₱<?php echo number_format($row['amount'] ?? 0, 2); ?></td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500 italic border border-slate-200">No labor expenses found.</td>
                            </tr>
                            <?php endif; ?>
                            
                            <tr class="bg-slate-50">
                                <td colspan="4" class="px-4 py-3 text-right text-sm font-black uppercase text-slate-600 border border-slate-300">Total Labor Expenses:</td>
                                <td class="px-4 py-3 text-right text-lg font-black text-slate-900 border border-slate-300 font-mono">₱<?php echo number_format($grand_total, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <?php
                    break;

                case 'cash-flow':
                    $flow = $payload['cash_flow'] ?? [];
                    ?>
                    <h3 class="text-sm font-bold text-slate-900 uppercase border-b border-slate-200 pb-2 mb-4">Monthly Cash Flow</h3>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-100">
                            <tr>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-center w-12">#</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Month</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Total Outflow</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand_total = 0;
                            if (count($flow) > 0): 
                                foreach ($flow as $index => $row): 
                                    $grand_total += $row['monthly_total'] ?? 0;
                                    $parts = explode('-', $row['month_year'] ?? '');
                                    $monthLabel = $row['month_year'] ?? '';
                                    if (count($parts) === 2) {
                                        $d = DateTime::createFromFormat('!Y-m', $row['month_year']);
                                        if ($d) $monthLabel = $d->format('F Y');
                                    }
                            ?>
                            <tr>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-500"><?php echo $index + 1; ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 font-medium text-slate-700"><?php echo htmlspecialchars($monthLabel); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono font-bold text-slate-900">₱<?php echo number_format($row['monthly_total'] ?? 0, 2); ?></td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-slate-500 italic border border-slate-200">No cash flow data found.</td>
                            </tr>
                            <?php endif; ?>
                            
                            <tr class="bg-slate-50">
                                <td colspan="2" class="px-4 py-3 text-right text-sm font-black uppercase text-slate-600 border border-slate-300">Total Cash Outflow:</td>
                                <td class="px-4 py-3 text-right text-lg font-black text-slate-900 border border-slate-300 font-mono">₱<?php echo number_format($grand_total, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <?php
                    break;

                case 'budget-summary':
                case 'budget_summary':
                    $expenses = $payload['expenses'] ?? [];
                    $totals = $payload['totals'] ?? [];
                    // Prefer approved_total for "Approved Expenses" when available
                    if (isset($totals['approved_total'])) {
                        $grand = $totals['approved_total'];
                    } else {
                        $grand = $totals['grand_total'] ?? array_sum(array_values($totals));
                    }
                    ?>
                    <h3 class="text-sm font-bold text-slate-900 uppercase border-b border-slate-200 pb-2 mb-4">Budget Overview</h3>
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="border border-slate-200 rounded-lg p-4 text-center">
                            <p class="text-xs font-bold text-slate-500 uppercase">Total Budget</p>
                            <p class="text-xl font-black text-slate-900 font-mono mt-1">₱<?php echo number_format($project['total_budget'] ?? 0, 2); ?></p>
                        </div>
                        <div class="border border-slate-200 rounded-lg p-4 text-center">
                            <p class="text-xs font-bold text-slate-500 uppercase">Actual Spending</p>
                            <p class="text-xl font-black text-slate-900 font-mono mt-1">₱<?php echo number_format($project['actual_spending'] ?? 0, 2); ?></p>
                        </div>
                        <div class="border border-slate-200 rounded-lg p-4 text-center">
                            <p class="text-xs font-bold text-slate-500 uppercase">Approved Expenses</p>
                            <p class="text-xl font-black text-slate-900 font-mono mt-1">₱<?php echo number_format($grand, 2); ?></p>
                        </div>
                    </div>
                    
                    <h3 class="text-sm font-bold text-slate-900 uppercase border-b border-slate-200 pb-2 mb-4">Expense Details</h3>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-100">
                            <tr>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-center w-12">#</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Date</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Category</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Description</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Supplier</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $expense_total = 0;
                            if (count($expenses) > 0): 
                                foreach ($expenses as $index => $e): 
                                    $expense_total += $e['amount'] ?? 0;
                            ?>
                            <tr>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-500"><?php echo $index + 1; ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 font-medium text-slate-700"><?php echo htmlspecialchars(date('M j, Y', strtotime($e['expense_date'] ?? ''))); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-slate-700"><?php echo htmlspecialchars($e['category'] ?? ''); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-slate-800"><?php echo htmlspecialchars($e['description'] ?? ''); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-slate-700"><?php echo htmlspecialchars($e['supplier_name'] ?? 'N/A'); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono font-bold text-slate-900">₱<?php echo number_format($e['amount'] ?? 0, 2); ?></td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500 italic border border-slate-200">No expenses found.</td>
                            </tr>
                            <?php endif; ?>
                            
                            <tr class="bg-slate-50">
                                <td colspan="5" class="px-4 py-3 text-right text-sm font-black uppercase text-slate-600 border border-slate-300">Total Expenses:</td>
                                <td class="px-4 py-3 text-right text-lg font-black text-slate-900 border border-slate-300 font-mono">₱<?php echo number_format($expense_total, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <?php
                    break;

                case 'cost-analysis':
                case 'cost_analysis':
                    $expenses = $payload['expenses'] ?? [];
                    $totals = $payload['totals'] ?? [];
                    
                    // Group expenses by category for analysis
                    $categoryTotals = [];
                    foreach ($expenses as $e) {
                        $cat = $e['category'] ?? 'Uncategorized';
                        if (!isset($categoryTotals[$cat])) {
                            $categoryTotals[$cat] = 0;
                        }
                        $categoryTotals[$cat] += $e['amount'] ?? 0;
                    }
                    arsort($categoryTotals);
                    $grandTotal = array_sum($categoryTotals);
                    ?>
                    <h3 class="text-sm font-bold text-slate-900 uppercase border-b border-slate-200 pb-2 mb-4">Cost Analysis by Category</h3>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-100">
                            <tr>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-center w-12">#</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Category</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Amount</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (count($categoryTotals) > 0): 
                                $index = 0;
                                foreach ($categoryTotals as $category => $amount): 
                                    $percentage = $grandTotal > 0 ? ($amount / $grandTotal) * 100 : 0;
                                    $index++;
                            ?>
                            <tr>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-500"><?php echo $index; ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 font-medium text-slate-700"><?php echo htmlspecialchars($category); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono text-slate-900">₱<?php echo number_format($amount, 2); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono font-bold text-slate-900"><?php echo number_format($percentage, 1); ?>%</td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-slate-500 italic border border-slate-200">No cost data found.</td>
                            </tr>
                            <?php endif; ?>
                            
                            <tr class="bg-slate-50">
                                <td colspan="2" class="px-4 py-3 text-right text-sm font-black uppercase text-slate-600 border border-slate-300">Total Costs:</td>
                                <td class="px-4 py-3 text-right text-lg font-black text-slate-900 border border-slate-300 font-mono">₱<?php echo number_format($grandTotal, 2); ?></td>
                                <td class="px-4 py-3 text-right text-lg font-black text-slate-900 border border-slate-300 font-mono">100%</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h3 class="text-sm font-bold text-slate-900 uppercase border-b border-slate-200 pb-2 mb-4 mt-8">Detailed Expense Breakdown</h3>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-100">
                            <tr>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-center w-12">#</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Date</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Category</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Description</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Supplier</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (count($expenses) > 0): 
                                foreach ($expenses as $index => $e): 
                            ?>
                            <tr>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-500"><?php echo $index + 1; ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 font-medium text-slate-700"><?php echo htmlspecialchars(date('M j, Y', strtotime($e['expense_date'] ?? ''))); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-slate-700"><?php echo htmlspecialchars($e['category'] ?? ''); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-slate-800"><?php echo htmlspecialchars($e['description'] ?? ''); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-slate-700"><?php echo htmlspecialchars($e['supplier_name'] ?? 'N/A'); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono font-bold text-slate-900">₱<?php echo number_format($e['amount'] ?? 0, 2); ?></td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500 italic border border-slate-200">No expenses found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php
                    break;

                default:
                    // expense-log or other types
                    $expenses = $payload['expenses'] ?? [];
                    ?>
                    <h3 class="text-sm font-bold text-slate-900 uppercase border-b border-slate-200 pb-2 mb-4">Expense Log</h3>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-100">
                            <tr>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-center w-12">#</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Date</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Category</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Description</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Supplier</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand_total = 0;
                            if (count($expenses) > 0): 
                                foreach ($expenses as $index => $e): 
                                    $grand_total += $e['amount'] ?? 0;
                            ?>
                            <tr>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-500"><?php echo $index + 1; ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 font-medium text-slate-700"><?php echo htmlspecialchars(date('M j, Y', strtotime($e['expense_date'] ?? ''))); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-slate-700"><?php echo htmlspecialchars($e['category'] ?? ''); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-slate-800"><?php echo htmlspecialchars($e['description'] ?? ''); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-slate-700"><?php echo htmlspecialchars($e['supplier_name'] ?? 'N/A'); ?></td>
                                <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono font-bold text-slate-900">₱<?php echo number_format($e['amount'] ?? 0, 2); ?></td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500 italic border border-slate-200">No expenses found.</td>
                            </tr>
                            <?php endif; ?>
                            
                            <tr class="bg-slate-50">
                                <td colspan="5" class="px-4 py-3 text-right text-sm font-black uppercase text-slate-600 border border-slate-300">Total Expenses:</td>
                                <td class="px-4 py-3 text-right text-lg font-black text-slate-900 border border-slate-300 font-mono">₱<?php echo number_format($grand_total, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <?php
                    break;
            }
            ?>
        </div>

        <div class="print-footer mt-12 pt-8">
            <div class="grid grid-cols-3 gap-8">
                <div class="text-center">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-12">Prepared By:</p>
                    <div class="border-b border-slate-800 w-3/4 mx-auto"></div>
                    <p class="text-sm font-bold mt-2 text-slate-900 uppercase"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Authorized Staff'); ?></p>
                    <p class="text-xs text-slate-500">Requestor</p>
                </div>

                <div class="text-center">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-12">Verified By:</p>
                    <div class="border-b border-slate-800 w-3/4 mx-auto"></div>
                    <p class="text-sm font-bold mt-2 text-slate-900 uppercase">Engr. Jane Doe</p> 
                    <p class="text-xs text-slate-500">Project Engineer</p>
                </div>

                <div class="text-center">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-12">Approved By:</p>
                    <div class="border-b border-slate-800 w-3/4 mx-auto"></div>
                    <p class="text-sm font-bold mt-2 text-slate-900 uppercase">John Smith</p> 
                    <p class="text-xs text-slate-500">Project Manager</p>
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
// end
?>