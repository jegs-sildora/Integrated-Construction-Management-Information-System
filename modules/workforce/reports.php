<?php
/**
 * Workforce Reports - Workforce Module
 * Main UI for selecting and generating reports.
 */
include __DIR__ . '/project_context.php';

$conn = getWorkforceConnection();
$selected_project_id = getProjectContext($conn);

// Get current project details
$current_project_name = "No Project Selected";
$current_project_code = "";

if ($selected_project_id) {
    $stmt = $conn->prepare("SELECT project_name, project_code FROM icmis_projects WHERE project_id = ?");
    $stmt->bind_param("i", $selected_project_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $current_project_name = $row['project_name'];
        $current_project_code = $row['project_code'];
    }
    $stmt->close();
}

$projects = [];
$result = $conn->query("SELECT project_id, project_code, project_name FROM icmis_projects ORDER BY project_id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}

// Fetch recent generated reports
$recent_reports = [];
$tableExists = $conn->query("SHOW TABLES LIKE 'workforce_generated_reports'");
if ($tableExists && $tableExists->num_rows > 0 && $selected_project_id) {
    $report_sql = "SELECT report_id, report_type, report_name, project_id, generated_by, created_at 
                   FROM workforce_generated_reports 
                   WHERE project_id = ? 
                   ORDER BY created_at DESC 
                   LIMIT 50";
    $stmt = $conn->prepare($report_sql);
    $stmt->bind_param("i", $selected_project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_reports[] = $row;
    }
    $stmt->close();
}

$userName = $_SESSION['user_name'] ?? "Admin";

// Build breadcrumb
$current_page = basename($_SERVER['PHP_SELF']);
$breadcrumbHTML = '<div class="flex items-center gap-2 text-sm">';
$breadcrumbHTML .= '<div class="relative inline-block">';
$breadcrumbHTML .= '<select id="projectSelector" onchange="window.location.href=\'' . $current_page . '?project_id=\' + this.value" class="appearance-none bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] pl-3 pr-8 py-1.5 hover:bg-gray-50 transition-colors cursor-pointer font-medium">';
foreach ($projects as $proj) {
    $selected = ($proj['project_id'] == $selected_project_id) ? 'selected' : '';
    $breadcrumbHTML .= '<option value="' . $proj['project_id'] . '" ' . $selected . '>' . htmlspecialchars($proj['project_name']) . '</option>';
}
$breadcrumbHTML .= '</select>';
$breadcrumbHTML .= '<svg class="w-3 h-3 text-gray-500 absolute right-2 top-1/2 transform -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
$breadcrumbHTML .= '</div>';
$breadcrumbHTML .= '</div>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workforce Reports | ICMIS</title>
    <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
    </head>
<body class="bg-gray-50 text-slate-800">
    
    <div class="no-print">
        <?php 
            include __DIR__ . '/../../includes/sidebar.php';
            include __DIR__ . '/../../includes/toast.php';
            include __DIR__ . '/../../includes/header.php'; 
        ?>
    </div>

    <main class="ml-56 mt-20 p-6 transition-all duration-300 animate-fade-in">
        <div class="max-w-7xl mx-auto">
            
            <div class="border-b border-gray-200 pb-6 mb-8 no-print">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl text-gray-900 font-bold">Workforce Reports</h1>
                        <p class="text-sm text-gray-500 mt-1">Generate and access workforce documentation for <span class="font-semibold text-[#e9922c]"><?php echo htmlspecialchars($current_project_name); ?></span></p>
                    </div>
                </div>
            </div>

            <div class="mb-10 no-print">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Quick Generate Templates</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-blue-300 hover:shadow-lg transition-all duration-200 report-template-card flex flex-col justify-between" data-template="employee-directory">
                        <div>
                            <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-xl mb-4 text-blue-600">
                                <i data-lucide="users" class="w-6 h-6"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Employee Directory</h3>
                            <p class="text-sm text-gray-600 mb-4">Complete list of all employees assigned to this project.</p>
                        </div>
                        <button onclick="generateReport('employee-directory')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-blue-600 text-blue-700 hover:bg-blue-50 font-semibold rounded-lg transition-colors">
                            <i data-lucide="printer" class="w-4 h-4"></i> Generate & Print
                        </button>
                    </div>

                    <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-green-300 hover:shadow-lg transition-all duration-200 report-template-card flex flex-col justify-between" data-template="attendance-summary">
                        <div>
                            <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-xl mb-4 text-green-600">
                                <i data-lucide="calendar-check" class="w-6 h-6"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Attendance Summary</h3>
                            <p class="text-sm text-gray-600 mb-4">Monthly attendance records and statistics.</p>
                            <div class="mb-4">
                                <input type="month" class="month-selector w-full text-xs bg-gray-50 border border-gray-200 px-2 py-1.5 rounded focus:border-green-500 outline-none" value="<?php echo date('Y-m'); ?>">
                            </div>
                        </div>
                        <button onclick="generateReport('attendance-summary')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-green-600 text-green-700 hover:bg-green-50 font-semibold rounded-lg transition-colors">
                            <i data-lucide="printer" class="w-4 h-4"></i> Generate & Print
                        </button>
                    </div>

                    <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-orange-300 hover:shadow-lg transition-all duration-200 report-template-card flex flex-col justify-between" data-template="assignment-report">
                        <div>
                            <div class="flex items-center justify-center w-12 h-12 bg-orange-100 rounded-xl mb-4 text-orange-600">
                                <i data-lucide="briefcase" class="w-6 h-6"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Assignment Report</h3>
                            <p class="text-sm text-gray-600 mb-4">Employee project assignments with roles and periods.</p>
                        </div>
                        <button onclick="generateReport('assignment-report')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-orange-600 text-orange-700 hover:bg-orange-50 font-semibold rounded-lg transition-colors">
                            <i data-lucide="printer" class="w-4 h-4"></i> Generate & Print
                        </button>
                    </div>

                    <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-purple-300 hover:shadow-lg transition-all duration-200 report-template-card flex flex-col justify-between" data-template="payroll-report">
                        <div>
                            <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-xl mb-4 text-purple-600">
                                <i data-lucide="banknote" class="w-6 h-6"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Payroll Report</h3>
                            <p class="text-sm text-gray-600 mb-4">Monthly payroll summary with deductions.</p>
                            <div class="mb-4">
                                <input type="month" class="payroll-month-selector w-full text-xs bg-gray-50 border border-gray-200 px-2 py-1.5 rounded focus:border-purple-500 outline-none" value="<?php echo date('Y-m'); ?>">
                            </div>
                        </div>
                        <button onclick="generateReport('payroll-report')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-purple-600 text-purple-700 hover:bg-purple-50 font-semibold rounded-lg transition-colors">
                            <i data-lucide="printer" class="w-4 h-4"></i> Generate & Print
                        </button>
                    </div>

                    <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-teal-300 hover:shadow-lg transition-all duration-200 report-template-card flex flex-col justify-between" data-template="workforce-analytics">
                        <div>
                            <div class="flex items-center justify-center w-12 h-12 bg-teal-100 rounded-xl mb-4 text-teal-600">
                                <i data-lucide="bar-chart-3" class="w-6 h-6"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Workforce Analytics</h3>
                            <p class="text-sm text-gray-600 mb-4">Overall workforce performance and utilization.</p>
                        </div>
                        <button onclick="generateReport('workforce-analytics')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-teal-600 text-teal-700 hover:bg-teal-50 font-semibold rounded-lg transition-colors">
                            <i data-lucide="printer" class="w-4 h-4"></i> Generate & Print
                        </button>
                    </div>

                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 no-print">
                    <div class="flex items-center gap-3">
                        <h3 class="font-bold text-gray-800">Recent Reports</h3>
                        <?php if($selected_project_id): ?>
                        <span class="text-xs font-medium text-gray-500 bg-white border border-gray-200 px-2 py-0.5 rounded-full">
                            Filtered by: <?php echo htmlspecialchars($current_project_name); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="location.reload()" class="text-sm text-gray-500 hover:text-[#e9922c] font-medium transition-colors flex items-center gap-1 px-2">
                            <i data-lucide="refresh-cw" class="w-3 h-3"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                            <tr>
                                <th class="px-6 py-3">Report Name</th>
                                <th class="px-6 py-3">Type</th>
                                <th class="px-6 py-3">Generated By</th>
                                <th class="px-6 py-3">Date Generated</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <?php if(empty($recent_reports)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500 bg-white">
                                    <div class="flex flex-col items-center">
                                        <i data-lucide="folder-open" class="w-8 h-8 text-gray-300 mb-2"></i>
                                        <p>No reports found for this project.</p>
                                        <p class="text-xs text-gray-400 mt-1">Generate a template above to see it here.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($recent_reports as $report): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-medium text-gray-700 flex items-center">
                                    <i data-lucide="file-text" class="w-4 h-4 text-gray-400 mr-2 no-print"></i>
                                    <?php echo htmlspecialchars($report['report_name'] ?? 'Untitled Report'); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php 
                                        $colors = [
                                            'employee-directory' => 'blue',
                                            'attendance-summary' => 'green',
                                            'assignment-report' => 'orange',
                                            'payroll-report' => 'purple',
                                            'workforce-analytics' => 'teal'
                                        ];
                                        $type = $report['report_type'] ?? 'default';
                                        $color = $colors[$type] ?? 'gray';
                                    ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-700 capitalize">
                                        <?php echo str_replace('-', ' ', $type); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-500"><?php echo htmlspecialchars($report['generated_by'] ?? 'System'); ?></td>
                                <td class="px-6 py-4 text-gray-500">
                                    <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($report['created_at'] ?? 'now'))); ?>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button onclick="generateReport('<?php echo $report['report_type']; ?>')" class="generate-btn p-2 text-gray-400 hover:text-[#e9922c] hover:bg-orange-50 rounded-full transition" title="Print Again">
                                        <i data-lucide="printer" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        window.reportsConfig = <?php echo json_encode([
            'projectId' => $selected_project_id,
            'projectName' => $current_project_name,
            'projectCode' => $current_project_code,
            'userName' => $userName
        ]); ?>;
    </script>
    <script src="js/reports.js"></script>
</body>
</html>
<?php $conn->close(); ?>