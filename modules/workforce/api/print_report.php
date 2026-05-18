<?php
// print_report.php
// Handles HTML rendering and Printing for Workforce Reports
session_start();
require_once __DIR__ . '/../../../core/Logger.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
include __DIR__ . '/../project_context.php';

if (!isset($_GET['project_id']) || !isset($_GET['type'])) {
    die('Project ID and Report Type are required');
}

$project_id = intval($_GET['project_id']);
$type = $_GET['type'];
$month = $_GET['month'] ?? date('Y-m');
$generatedBy = $_SESSION['user_name'] ?? 'System';

// 1. Fetch Project Details via API
$project_name = "Unknown Project";
$project_code = "N/A";
$projRes = ApiHelper::get("project/projects/$project_id");
if ($projRes['status'] === 200 && !empty($projRes['data'])) {
    $project_name = $projRes['data']['project_name'];
    $project_code = $projRes['data']['project_code'];
}

// 2. Log Generation via Centralized Reports Service
$reportTitles = [
    'employee-directory' => 'Employee Directory',
    'attendance-summary' => 'Attendance Summary',
    'assignment-report' => 'Assignment Report',
    'payroll-report' => 'Payroll Report',
    'workforce-analytics' => 'Workforce Analytics'
];
$reportName = $reportTitles[$type] ?? 'Workforce Report';
if($type === 'attendance-summary' || $type === 'payroll-report') {
    $reportName .= ' (' . date('M Y', strtotime($month)) . ')';
}

// POST to Reports Service
ApiHelper::call('reports/reports', 'POST', [
    'project_id' => $project_id,
    'report_type' => $type,
    'report_name' => $reportName,
    'category' => 'workforce',
    'generated_by' => $generatedBy
]);

// 3. Fetch Data Based on Type via Workforce Service
$data = [];
$stats = [];
$tableHeaders = [];
$tableRows = [];
$reportDateInfo = date('F j, Y');

switch ($type) {
    case 'employee-directory':
        $reportDateInfo = "Current Staff List";
        $res = ApiHelper::get("workforce/employees?project_id=$project_id&limit=1000");
        $data = $res['data']['data'] ?? [];
        
        $active = count(array_filter($data, fn($i) => $i['status'] === 'Active'));
        $stats = [
            ['label' => 'Total Staff', 'value' => count($data)],
            ['label' => 'Active', 'value' => $active, 'color' => 'text-blue-700'],
            ['label' => 'Inactive', 'value' => count($data) - $active]
        ];
        
        $tableHeaders = ['Code', 'Name', 'Role', 'Email', 'Phone', 'Status'];
        foreach($data as $r) {
            $statusClass = $r['status'] === 'Active' ? 'text-blue-700 font-bold' : 'text-slate-500';
            $tableRows[] = [
                '<span class="font-mono">'.($r['employee_code'] ?? 'N/A').'</span>',
                ($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''),
                $r['job_title'] ?? 'Staff',
                $r['email'] ?? '-',
                $r['phone'] ?? '-',
                '<span class="'.$statusClass.'">'.($r['status'] ?? 'Active').'</span>'
            ];
        }
        break;

    case 'attendance-summary':
        $reportDateInfo = "Period: " . date('F Y', strtotime($month));
        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $res = ApiHelper::get("workforce/reports?type=attendance-summary&project_id=$project_id&start_date=$start_date&end_date=$end_date");
        $data = $res['data']['data'] ?? [];

        // Stats
        $totalHours = array_sum(array_column($data, 'hours'));
        $avgRate = count($data) > 0 ? array_sum(array_column($data, 'rate')) / count($data) : 0;
        
        $stats = [
            ['label' => 'Employees', 'value' => count($data)],
            ['label' => 'Avg Attendance', 'value' => number_format($avgRate, 1).'%', 'color' => ($avgRate>90 ? 'text-green-700' : 'text-slate-800')],
            ['label' => 'Total Man-Hours', 'value' => number_format($totalHours)]
        ];

        $tableHeaders = ['Code', 'Name', 'Present', 'Absent', 'Leave', 'Total Hours', 'Rate'];
        foreach($data as $r) {
            $tableRows[] = [
                '<span class="font-mono">'.($r['employee_code'] ?? 'N/A').'</span>',
                $r['name'] ?? 'N/A',
                $r['present'] ?? 0,
                $r['absent'] ?? 0,
                $r['leave_days'] ?? 0,
                $r['hours'] ?? 0,
                '<span class="font-bold">'.($r['rate'] ?? 0).'%</span>'
            ];
        }
        break;

    case 'payroll-report':
        $reportDateInfo = "Period: " . date('F Y', strtotime($month));
        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));

        $res = ApiHelper::get("workforce/reports?type=payroll-report&project_id=$project_id&start_date=$start_date&end_date=$end_date");
        $data = $res['data']['data'] ?? [];
        
        $totalGross = array_sum(array_column($data, 'gross')); 
        $totalDed = array_sum(array_column($data, 'ded')); 
        $totalNet = array_sum(array_column($data, 'net'));

        $stats = [
            ['label' => 'Total Gross', 'value' => '₱'.number_format($totalGross, 2)],
            ['label' => 'Deductions', 'value' => '₱'.number_format($totalDed, 2), 'color' => 'text-red-700'],
            ['label' => 'Total Net Pay', 'value' => '₱'.number_format($totalNet, 2), 'color' => 'text-blue-700']
        ];

        $tableHeaders = ['Code', 'Name', 'Role', 'Days', 'Daily Rate', 'Gross Pay', 'Deductions', 'Net Pay'];
        foreach($data as $r) {
            $tableRows[] = [
                '<span class="font-mono">'.($r['employee_code'] ?? 'N/A').'</span>',
                $r['name'] ?? 'N/A',
                $r['job_title'] ?? 'N/A',
                $r['days_worked'] ?? 0,
                number_format($r['daily_rate'] ?? 0, 2),
                number_format($r['gross'] ?? 0, 2),
                '<span class="text-red-700">('.number_format($r['ded'] ?? 0, 2).')</span>',
                '<span class="font-bold">'.number_format($r['net'] ?? 0, 2).'</span>'
            ];
        }
        break;

    case 'assignment-report':
        $reportDateInfo = "Active Assignments";
        $res = ApiHelper::get("workforce/reports?type=assignment-report&project_id=$project_id");
        $data = $res['data']['data'] ?? [];

        $active = count(array_filter($data, fn($i) => ($i['status'] ?? '') === 'Active'));
        $stats = [
            ['label' => 'Total Records', 'value' => count($data)],
            ['label' => 'Active', 'value' => $active, 'color' => 'text-green-700'],
            ['label' => 'Completed', 'value' => count($data) - $active]
        ];

        $tableHeaders = ['Employee', 'Phase', 'Role', 'Start Date', 'End Date', 'Status'];
        foreach($data as $r) {
            $tableRows[] = [
                $r['name'] ?? 'N/A', 
                $r['phase'] ?? '-', 
                $r['role'] ?? '-', 
                $r['start_date'] ?: '-', 
                $r['end_date'] ?: '-',
                '<span class="font-bold '.(($r['status'] ?? '')=='Active'?'text-green-700':'text-slate-500').'">'.($r['status'] ?? 'N/A').'</span>'
            ];
        }
        break;

    case 'workforce-analytics':
        $reportDateInfo = "Performance Metrics";
        $res = ApiHelper::get("workforce/reports?type=workforce-analytics&project_id=$project_id");
        $breakdown = $res['data']['breakdown'] ?? [];
        $total = $res['data']['total'] ?? 0;

        $stats = [
            ['label' => 'Total Assignments', 'value' => $total],
            ['label' => 'Utilization', 'value' => '100%'],
            ['label' => 'Status Groups', 'value' => count($breakdown)]
        ];

        $tableHeaders = ['Status Group', 'Total Count', 'Percentage'];
        foreach($breakdown as $r) {
            $pct = $total > 0 ? round(($r['count']/$total)*100, 1) : 0;
            $tableRows[] = [
                '<span class="font-bold">'.($r['status'] ?? 'N/A').'</span>',
                $r['count'] ?? 0,
                $pct.'%'
            ];
        }
        break;
}

// Close Connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($reportName); ?></title>
    <?php include __DIR__ . '/../../../includes/head_assetsv2.php'; ?>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; padding: 40px; }
        .report-container { max-width: 1000px; margin: 0 auto; background: white; padding: 40px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border-radius: 8px; }
        @media print {
            @page { margin: 0.5in; size: auto; }
            body { background-color: white !important; color: black !important; padding: 0 !important; -webkit-print-color-adjust: exact; }
            .report-container { box-shadow: none !important; padding: 0 !important; width: 100% !important; max-width: none !important; }
            .no-print { display: none !important; }
            table { width: 100% !important; border-collapse: collapse !important; font-size: 10pt !important; }
            thead tr { background-color: #f3f4f6 !important; }
            thead th { border: 1px solid #9ca3af !important; padding: 8px !important; color: black !important; font-weight: bold !important; text-transform: uppercase !important; }
            tbody td { border: 1px solid #e5e7eb !important; padding: 8px !important; color: black !important; }
            .print-footer { margin-top: 50px !important; page-break-inside: avoid; }
        }
        .print-logo { height: 80px; width: auto; margin: 0 auto 10px auto; display: block; }
    </style>
</head>
<body>
    <div class="report-container">
        
        <div class="text-center border-b-2 border-slate-800 pb-6 mb-8">
            <img src="../../../assets/images/nobg_logo.png" alt="ICMIS Logo" class="print-logo">
            <h1 class="text-2xl font-black uppercase tracking-wide text-slate-900 mt-2"><?php echo htmlspecialchars($reportName); ?></h1>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-widest">Integrated Construction Management Information System</p>
            <p class="text-xs text-slate-400 mt-1">Generated on: <?php echo date('F j, Y h:i A'); ?></p>
        </div>

        <div class="grid grid-cols-2 gap-8 mb-8 text-sm">
            <div>
                <table class="w-full">
                    <tr><td class="font-bold text-slate-500 py-1 w-32">Project:</td><td class="font-bold text-slate-900 py-1"><?php echo htmlspecialchars($project_name); ?></td></tr>
                    <tr><td class="font-bold text-slate-500 py-1">Project Code:</td><td class="font-mono font-bold text-slate-700 py-1"><?php echo htmlspecialchars($project_code); ?></td></tr>
                </table>
            </div>
            <div>
                <table class="w-full">
                    <tr><td class="font-bold text-slate-500 py-1 w-32">Context:</td><td class="text-slate-900 py-1"><?php echo htmlspecialchars($reportDateInfo); ?></td></tr>
                    <tr><td class="font-bold text-slate-500 py-1">Generated By:</td><td class="text-slate-900 py-1"><?php echo htmlspecialchars($generatedBy); ?></td></tr>
                </table>
            </div>
        </div>

        <?php if(!empty($stats)): ?>
        <div class="mb-8 bg-slate-50 rounded-lg border border-slate-200 p-6">
            <h3 class="text-xs font-bold text-slate-500 uppercase mb-4">Report Overview</h3>
            <div class="grid grid-cols-<?php echo count($stats); ?> gap-4 text-center">
                <?php foreach($stats as $idx => $stat): ?>
                <div class="p-2 <?php echo ($idx < count($stats)-1) ? 'border-r border-slate-200' : ''; ?>">
                    <p class="text-xs text-slate-500 uppercase font-bold"><?php echo $stat['label']; ?></p>
                    <p class="text-xl font-black <?php echo $stat['color'] ?? 'text-slate-800'; ?> mt-1 font-mono"><?php echo $stat['value']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="mb-8">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-100">
                    <tr>
                        <?php foreach($tableHeaders as $th): ?>
                        <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300"><?php echo $th; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($tableRows) > 0): ?>
                        <?php foreach($tableRows as $row): ?>
                        <tr>
                            <?php foreach($row as $cell): ?>
                            <td class="px-4 py-2 text-sm border border-slate-200 text-slate-700"><?php echo $cell; ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="<?php echo count($tableHeaders); ?>" class="px-4 py-8 text-center text-slate-500 italic">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="print-footer mt-12 pt-8">
            <div class="grid grid-cols-3 gap-8">
                <div class="text-center">
                    <p class="text-xs font-bold text-slate-500 uppercase mb-12">Prepared By:</p>
                    <div class="border-b border-slate-800 w-3/4 mx-auto"></div>
                    <p class="text-sm font-bold mt-2 text-slate-900 uppercase"><?php echo htmlspecialchars($generatedBy); ?></p>
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
            <button onclick="window.print()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold shadow-md transition-colors">Print Report</button>
        </div>
    </div>

    <script>
        window.onload = function() { setTimeout(() => { window.print(); }, 500); };
    </script>
</body>
</html>