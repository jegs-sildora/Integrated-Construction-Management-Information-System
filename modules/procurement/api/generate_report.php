<?php
// generate_report.php - server-side printable report generator
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/project_context.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

// Get params - fallback to centralized context if not in GET
$report_type = $_GET['type'] ?? 'inventory';
$project_id = $_GET['project_id'] ?? ProjectContext::getProjectId();

// Fetch data via Microservice API Gateway
$api_url = "procurement/reports?type=$report_type&project_id=$project_id";
$res = ApiHelper::get($api_url);

if ($res['status'] !== 200) {
    echo "<h2>Error</h2><p>Failed to fetch report data from microservice.</p>";
    exit;
}

$data = $res['data'];
if (!$data['success']) {
    $msg = $data['message'] ?? 'Failed to generate report data';
    echo "<h2>Error</h2><p>" . htmlspecialchars($msg) . "</p>";
    exit;
}

$payload = $data['data'];
$project = $payload['project'] ?? [];

// Log Generation via Centralized Reports Service
$reportName = $title;
if (!empty($project['project_name'])) {
    $reportName .= ' - ' . $project['project_name'];
}

$generatedBy = $_SESSION['user_name'] ?? 'System';

ApiHelper::call('reports/reports', 'POST', [
    'project_id' => intval($project['project_id'] ?? $project_id),
    'report_type' => $report_type,
    'report_name' => $reportName,
    'category' => 'procurement',
    'generated_by' => $generatedBy
]);

// Determine Prepared By (session user or API fallback)
$prepared_by = 'Authorized Staff';
if (!empty($_SESSION['user_name'])) {
    $prepared_by = $_SESSION['user_name'];
} elseif (!empty($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $res_users = ApiHelper::get('auth/users');
    if ($res_users['status'] === 200) {
        foreach ($res_users['data'] as $u) {
            if (intval($u['user_id']) === $uid) {
                $prepared_by = $u['full_name'] ?? 'Admin';
                break;
            }
        }
    }
}

// Config for icons and titles
$config = [
    'inventory' => ['title' => 'Inventory Status Report', 'icon' => 'fa-boxes-stacked', 'color' => '#6366f1'],
    'orders' => ['title' => 'Purchase Orders Summary', 'icon' => 'fa-file-invoice-dollar', 'color' => '#0ea5e9'],
    'movement' => ['title' => 'Stock Movement Analysis', 'icon' => 'fa-truck-ramp-box', 'color' => '#ec4899']
];

$title = $config[$report_type]['title'] ?? 'Procurement Report';
$icon = $config[$report_type]['icon'] ?? 'fa-file-lines';
$theme = $config[$report_type]['color'] ?? '#334155';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?> | ICMIS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { padding: 0 !important; background: white !important; }
            .report-card { border: none !important; box-shadow: none !important; margin: 0 !important; width: 100% !important; }
        }
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; padding: 40px; }
        .report-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; max-width: 1000px; margin: 0 auto; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

    <div class="no-print mb-6 max-w-[1000px] mx-auto flex justify-between items-center">
        <a href="../reports.php" class="text-slate-500 hover:text-slate-800 flex items-center gap-2 transition-colors">
            <i class="fa-solid fa-arrow-left"></i> Back to Reports
        </a>
        <button onclick="window.print()" class="bg-indigo-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-indigo-700 shadow-lg flex items-center gap-2 transition-all">
            <i class="fa-solid fa-print"></i> Print Report
        </button>
    </div>

    <div class="report-card">
        <!-- Header -->
        <div class="p-8 border-b-4" style="border-color: <?php echo $theme; ?>">
            <div class="flex justify-between items-start">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-xl flex items-center justify-center text-white text-3xl shadow-lg" style="background: <?php echo $theme; ?>">
                        <i class="fa-solid <?php echo $icon; ?>"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tight"><?php echo $title; ?></h1>
                        <p class="text-slate-500 font-medium">ICMIS Procurement & Inventory Module</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Date Generated</p>
                    <p class="text-lg font-bold text-slate-700"><?php echo date('F j, Y'); ?></p>
                </div>
            </div>
        </div>

        <!-- Project Meta -->
        <div class="bg-slate-50 p-6 grid grid-cols-3 gap-8 border-b border-slate-200">
            <div>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Project Name</span>
                <span class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($project['project_name'] ?? 'All Projects'); ?></span>
            </div>
            <div>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Project Code</span>
                <span class="text-sm font-bold font-mono text-slate-800"><?php echo htmlspecialchars($project['project_code'] ?? 'GLOBAL'); ?></span>
            </div>
            <div>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Project Status</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-blue-100 text-blue-700 border border-blue-200">
                    <?php echo htmlspecialchars($project['status'] ?? 'Active'); ?>
                </span>
            </div>
        </div>

        <!-- Table Data -->
        <div class="p-8">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[11px] font-black text-slate-500 uppercase tracking-wider border-b-2 border-slate-100">
                        <?php foreach($payload['headers'] as $h): ?>
                            <th class="pb-3 px-2"><?php echo $h; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($payload['rows'])): ?>
                        <tr><td colspan="10" class="py-12 text-center text-slate-400 italic">No data records found for this criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach($payload['rows'] as $row): ?>
                            <tr class="text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                                <?php foreach($row as $cell): ?>
                                    <td class="py-4 px-2"><?php echo htmlspecialchars($cell); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="p-8 bg-slate-50 border-t border-slate-200">
            <div class="flex justify-between items-end">
                <div class="flex gap-12">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-8">Prepared By</p>
                        <div class="w-48 border-b-2 border-slate-900 pb-1">
                            <p class="text-sm font-bold text-slate-900 uppercase"><?php echo htmlspecialchars($prepared_by); ?></p>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1 font-medium italic">Authorized Signature</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-8">System Verified</p>
                        <div class="w-48 border-b-2 border-slate-300 pb-1 text-slate-300 italic text-sm">
                            Automated Check
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-slate-400 font-medium">Page 1 of 1</p>
                    <p class="text-[10px] text-slate-400 font-medium">ICMIS v1.0.0-PRO</p>
                </div>
            </div>
        </div>
    </div>

    <script>
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