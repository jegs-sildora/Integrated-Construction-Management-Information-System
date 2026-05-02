<?php
// print_report.php
// Handles HTML rendering and Printing for Workforce Reports
session_start();
require_once __DIR__ . '/../../../core/Logger.php';
include __DIR__ . '/../project_context.php'; // Adjust path if this file is in api/ folder

// Database Connection
$conn = getWorkforceConnection();

if (!isset($_GET['project_id']) || !isset($_GET['type'])) {
    die('Project ID and Report Type are required');
}

$project_id = intval($_GET['project_id']);
$type = $_GET['type'];
$month = $_GET['month'] ?? date('Y-m');
$generatedBy = 'System';
if (!empty($_SESSION['user_name'])) {
    $generatedBy = $_SESSION['user_name'];
} elseif (!empty($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    // Try to get user name from project API instead of direct DB
    $res_users = ApiHelper::get('auth/users');
    if ($res_users['status'] === 200) {
        foreach ($res_users['data'] as $u) {
            if (intval($u['user_id']) === $uid) {
                $generatedBy = $u['full_name'] ?? 'Admin';
                break;
            }
        }
    }
}

// 1. Fetch Project Details
$project_name = "Unknown Project";
$project_code = "N/A";
$sql_proj = "SELECT project_name, project_code FROM icmis_projects WHERE project_id = ?";
$stmt = $conn->prepare($sql_proj);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$res = $stmt->get_result();
if($r = $res->fetch_assoc()) {
    $project_name = $r['project_name'];
    $project_code = $r['project_code'];
}
$stmt->close();

// 2. Log Generation (Insert into workforce_generated_reports)
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

$checkTable = $conn->query("SHOW TABLES LIKE 'workforce_generated_reports'");
if ($checkTable && $checkTable->num_rows > 0) {
    $logSql = "INSERT INTO workforce_generated_reports (project_id, report_type, report_name, generated_by, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmtLog = $conn->prepare($logSql);
    $stmtLog->bind_param("isss", $project_id, $type, $reportName, $generatedBy);
    $stmtLog->execute();
    $stmtLog->close();
    
    // Log to Global Audit Trail
    Logger::init($conn);
    Logger::export('Workforce', "Generated Report: $reportName" . ($project_id > 0 ? " for $project_name" : ""), $project_id > 0 ? $project_id : null);
}

// 3. Fetch Data Based on Type
$data = [];
$stats = [];
$tableHeaders = [];
$tableRows = [];
$reportDateInfo = date('F j, Y');

switch ($type) {
    case 'employee-directory':
        $reportDateInfo = "Current Staff List";
        // Logic
        $sql = "SELECT e.employee_code, CONCAT(e.last_name, ', ', e.first_name) as name, 
                       a.role, e.email, e.phone, e.status
                FROM workforce_employees e
                JOIN workforce_assignments a ON e.employee_id = a.employee_id
                WHERE a.project_id = ? ORDER BY e.last_name";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()) { $data[] = $row; }
        
        // Stats
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
                '<span class="font-mono">'.$r['employee_code'].'</span>',
                $r['name'],
                $r['role'],
                $r['email'],
                $r['phone'],
                '<span class="'.$statusClass.'">'.$r['status'].'</span>'
            ];
        }
        break;

    case 'attendance-summary':
        $reportDateInfo = "Period: " . date('F Y', strtotime($month));
        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $sql = "SELECT e.employee_code, CONCAT(e.first_name, ' ', e.last_name) as name,
                       COUNT(CASE WHEN att.status = 'Present' THEN 1 END) as present,
                       COUNT(CASE WHEN att.status = 'Absent' THEN 1 END) as absent,
                       COUNT(CASE WHEN att.status = 'On Leave' THEN 1 END) as leave_days,
                       COALESCE(SUM(TIMESTAMPDIFF(HOUR, att.time_in, att.time_out)), 0) as hours
                FROM workforce_employees e
                JOIN workforce_assignments a ON e.employee_id = a.employee_id
                LEFT JOIN workforce_attendance att ON e.employee_id = att.employee_id 
                    AND att.project_id = ? AND att.attendance_date BETWEEN ? AND ?
                WHERE a.project_id = ? AND a.status = 'Active'
                GROUP BY e.employee_id ORDER BY e.last_name";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issi", $project_id, $start_date, $end_date, $project_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()) { 
            $total = $row['present'] + $row['absent'] + $row['leave_days'];
            $row['rate'] = $total > 0 ? round(($row['present']/$total)*100, 1) : 0;
            $data[] = $row; 
        }

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
                '<span class="font-mono">'.$r['employee_code'].'</span>',
                $r['name'],
                $r['present'],
                $r['absent'],
                $r['leave_days'],
                $r['hours'],
                '<span class="font-bold">'.$r['rate'].'%</span>'
            ];
        }
        break;

    case 'payroll-report':
        $reportDateInfo = "Period: " . date('F Y', strtotime($month));
        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));

        $sql = "SELECT e.employee_code, CONCAT(e.first_name, ' ', e.last_name) as name,
                       COALESCE(jt.default_daily_rate, 800) as daily_rate,
                       jt.title_name as job_title,
                       COUNT(CASE WHEN att.status IN ('Present', 'Late') THEN 1 END) as days_worked
                FROM workforce_employees e
                JOIN workforce_assignments a ON e.employee_id = a.employee_id
                LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
                LEFT JOIN workforce_attendance att ON e.employee_id = att.employee_id 
                    AND att.project_id = ? AND att.attendance_date BETWEEN ? AND ?
                WHERE a.project_id = ? AND a.status = 'Active'
                GROUP BY e.employee_id ORDER BY e.last_name";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issi", $project_id, $start_date, $end_date, $project_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $totalGross = 0; $totalDed = 0; $totalNet = 0;

        while($row = $res->fetch_assoc()) {
            $gross = $row['days_worked'] * $row['daily_rate'];
            $ded = $gross * 0.05; // 5% flat deduction logic
            $net = $gross - $ded;
            
            $totalGross += $gross;
            $totalDed += $ded;
            $totalNet += $net;

            $data[] = array_merge($row, ['gross'=>$gross, 'ded'=>$ded, 'net'=>$net]);
        }

        $stats = [
            ['label' => 'Total Gross', 'value' => '₱'.number_format($totalGross, 2)],
            ['label' => 'Deductions', 'value' => '₱'.number_format($totalDed, 2), 'color' => 'text-red-700'],
            ['label' => 'Total Net Pay', 'value' => '₱'.number_format($totalNet, 2), 'color' => 'text-blue-700']
        ];

        $tableHeaders = ['Code', 'Name', 'Role', 'Days', 'Daily Rate', 'Gross Pay', 'Deductions', 'Net Pay'];
        foreach($data as $r) {
            $tableRows[] = [
                '<span class="font-mono">'.$r['employee_code'].'</span>',
                $r['name'],
                $r['job_title'],
                $r['days_worked'],
                number_format($r['daily_rate'], 2),
                number_format($r['gross'], 2),
                '<span class="text-red-700">('.number_format($r['ded'], 2).')</span>',
                '<span class="font-bold">'.number_format($r['net'], 2).'</span>'
            ];
        }
        break;

    case 'assignment-report':
        $reportDateInfo = "Active Assignments";
        $sql = "SELECT CONCAT(e.first_name, ' ', e.last_name) as name,
                   COALESCE(ph.phase_name, '-') as phase,
                   a.role, a.start_date, a.end_date, a.status
            FROM workforce_assignments a
            JOIN workforce_employees e ON a.employee_id = e.employee_id
            LEFT JOIN icmis_project_phases ph ON a.phase_id = ph.phase_id
            WHERE a.project_id = ? ORDER BY a.status, e.last_name";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()) { $data[] = $row; }

        $active = count(array_filter($data, fn($i) => $i['status'] === 'Active'));
        $stats = [
            ['label' => 'Total Records', 'value' => count($data)],
            ['label' => 'Active', 'value' => $active, 'color' => 'text-green-700'],
            ['label' => 'Completed', 'value' => count($data) - $active]
        ];

        $tableHeaders = ['Employee', 'Phase', 'Role', 'Start Date', 'End Date', 'Status'];
        foreach($data as $r) {
            $tableRows[] = [
                $r['name'], $r['phase'], $r['role'], 
                $r['start_date'] ?: '-', $r['end_date'] ?: '-',
                '<span class="font-bold '.($r['status']=='Active'?'text-green-700':'text-slate-500').'">'.$r['status'].'</span>'
            ];
        }
        break;

    case 'workforce-analytics':
        $reportDateInfo = "Performance Metrics";
        // Simple analytics logic
        $sql = "SELECT status, COUNT(*) as count FROM workforce_assignments WHERE project_id = ? GROUP BY status";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $breakdown = [];
        $total = 0;
        while($r = $res->fetch_assoc()){ 
            $breakdown[] = $r; 
            $total += $r['count'];
        }

        $stats = [
            ['label' => 'Total Assignments', 'value' => $total],
            ['label' => 'Utilization', 'value' => '100%'],
            ['label' => 'Status Groups', 'value' => count($breakdown)]
        ];

        $tableHeaders = ['Status Group', 'Total Count', 'Percentage'];
        foreach($breakdown as $r) {
            $pct = $total > 0 ? round(($r['count']/$total)*100, 1) : 0;
            $tableRows[] = [
                '<span class="font-bold">'.$r['status'].'</span>',
                $r['count'],
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