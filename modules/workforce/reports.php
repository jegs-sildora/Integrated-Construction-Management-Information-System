<?php
/**
 * Workforce Reports - Workforce Module
 * 
 * Generate and manage workforce-related reports with PDF export
 * Following the UI pattern from budget/reports.php
 */
include __DIR__ . '/project_context.php';
require_once __DIR__ . '/../../includes/report_print_layout.php';

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

// Fetch all projects for dropdown
$projects = [];
$result = $conn->query("SELECT project_id, project_code, project_name FROM icmis_projects ORDER BY project_id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}

// Fetch recent generated reports (if table exists)
$recent_reports = [];
$tableExists = $conn->query("SHOW TABLES LIKE 'budget_generated_reports'");
if ($tableExists && $tableExists->num_rows > 0 && $selected_project_id) {
    $report_sql = "SELECT report_id, report_type, report_name, project_id, generated_by, created_at FROM budget_generated_reports 
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

// Get workforce statistics for reports
$stats = [
    'total_employees' => 0,
    'active_employees' => 0,
    'total_assignments' => 0,
    'attendance_rate' => 0
];

if ($selected_project_id) {
    // Total employees assigned
    $sql = "SELECT COUNT(DISTINCT a.employee_id) as count 
            FROM workforce_assignments a 
            WHERE a.project_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_employees'] = $result->fetch_assoc()['count'] ?? 0;
    $stmt->close();

    // Active employees
    $sql = "SELECT COUNT(DISTINCT a.employee_id) as count 
            FROM workforce_assignments a 
            WHERE a.project_id = ? AND a.status = 'Active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['active_employees'] = $result->fetch_assoc()['count'] ?? 0;
    $stmt->close();
    
    // Total assignments
    $sql = "SELECT COUNT(*) as count FROM workforce_assignments WHERE project_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_assignments'] = $result->fetch_assoc()['count'] ?? 0;
    $stmt->close();
}

// Build breadcrumb HTML
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

$userName = $_SESSION['user_name'] ?? "Admin";

// Set page variables
$pageSection = "Labor & Workforce";
$pageTitle = "Workforce Reports";
$pageSubTitle = $breadcrumbHTML;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workforce Reports | ICMIS</title>
    <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    
    <style>
        <?php echo renderPrintStyles(); ?>
    </style>
</head>
<body class="bg-gray-50 text-slate-800">
    
    <div class="no-print">
        <?php 
            include __DIR__ . '/../../includes/sidebar.php';
            include __DIR__ . '/../../includes/toast.php';
            include __DIR__ . '/../../includes/header.php'; 
        ?>
    </div>

    <!-- Print Header -->
    <?php echo renderPrintHeader('Workforce Reports Log', [
        'project_name' => $current_project_name,
        'project_code' => $current_project_code,
        'report_type' => 'Workforce Reports',
        'generated_by' => $userName
    ]); ?>

    <main class="ml-56 mt-20 p-6 transition-all duration-300">
        <div class="max-w-7xl mx-auto">
            
            <!-- Page Header -->
            <div class="border-b border-gray-200 pb-6 mb-8 no-print">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl text-gray-900 font-bold">Workforce Reports</h1>
                        <p class="text-sm text-gray-500 mt-1">Generate and access workforce documentation for <span class="font-semibold text-[#e9922c]"><?php echo htmlspecialchars($current_project_name); ?></span></p>
                    </div>
                </div>
            </div>

            <!-- Quick Generate Templates -->
            <div class="mb-10 no-print">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Quick Generate Templates</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <!-- Employee Directory -->
                    <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-blue-300 hover:shadow-lg transition-all duration-200 report-template-card flex flex-col justify-between" data-template="employee-directory">
                        <div>
                            <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-xl mb-4 text-blue-600">
                                <i data-lucide="users" class="w-6 h-6"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Employee Directory</h3>
                            <p class="text-sm text-gray-600 mb-4">Complete list of all employees assigned to this project.</p>
                        </div>
                        <button onclick="generateReport('employee-directory')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-blue-600 text-blue-700 hover:bg-blue-50 font-semibold rounded-lg transition-colors">
                            <i data-lucide="download" class="w-4 h-4"></i> Generate
                        </button>
                    </div>

                    <!-- Attendance Summary -->
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
                            <i data-lucide="download" class="w-4 h-4"></i> Generate
                        </button>
                    </div>

                    <!-- Assignment Report -->
                    <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-orange-300 hover:shadow-lg transition-all duration-200 report-template-card flex flex-col justify-between" data-template="assignment-report">
                        <div>
                            <div class="flex items-center justify-center w-12 h-12 bg-orange-100 rounded-xl mb-4 text-orange-600">
                                <i data-lucide="briefcase" class="w-6 h-6"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Assignment Report</h3>
                            <p class="text-sm text-gray-600 mb-4">Employee project assignments with roles and periods.</p>
                        </div>
                        <button onclick="generateReport('assignment-report')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-orange-600 text-orange-700 hover:bg-orange-50 font-semibold rounded-lg transition-colors">
                            <i data-lucide="download" class="w-4 h-4"></i> Generate
                        </button>
                    </div>

                    <!-- Payroll Report -->
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
                            <i data-lucide="download" class="w-4 h-4"></i> Generate
                        </button>
                    </div>

                    <!-- Workforce Analytics -->
                    <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-teal-300 hover:shadow-lg transition-all duration-200 report-template-card flex flex-col justify-between" data-template="workforce-analytics">
                        <div>
                            <div class="flex items-center justify-center w-12 h-12 bg-teal-100 rounded-xl mb-4 text-teal-600">
                                <i data-lucide="bar-chart-3" class="w-6 h-6"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Workforce Analytics</h3>
                            <p class="text-sm text-gray-600 mb-4">Overall workforce performance and utilization.</p>
                        </div>
                        <button onclick="generateReport('workforce-analytics')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-teal-600 text-teal-700 hover:bg-teal-50 font-semibold rounded-lg transition-colors">
                            <i data-lucide="download" class="w-4 h-4"></i> Generate
                        </button>
                    </div>

                </div>
            </div>

            <!-- Recent Reports Table -->
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
                        <button onclick="printReport()" class="text-sm text-gray-600 hover:text-[#e9922c] font-medium transition-colors flex items-center gap-1 px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50">
                            <i data-lucide="printer" class="w-3 h-3"></i> Print List
                        </button>
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
                                <th class="px-6 py-3">Context</th>
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
                                <td class="px-6 py-4 text-gray-500"><?php echo htmlspecialchars($report['context'] ?? $current_project_name); ?></td>
                                <td class="px-6 py-4 text-gray-500">
                                    <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($report['created_at'] ?? 'now'))); ?>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button onclick="generateReport('<?php echo $report['report_type']; ?>')" class="generate-btn p-2 text-gray-400 hover:text-[#e9922c] hover:bg-orange-50 rounded-full transition" title="Download Again">
                                        <i data-lucide="download" class="w-4 h-4"></i>
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

        <!-- Print Footer -->
        <?php echo renderPrintFooter($userName, 'Workforce Manager'); ?>
    </main>

    <script>
        // Initialize Icons
        lucide.createIcons();

        // Print functionality (shared)
        <?php echo renderPrintReportScript(); ?>

        // Loading state management
        function setLoadingState(button) {
            if(!button) return;
            button.dataset.originalContent = button.innerHTML;
            button.dataset.originalClasses = button.className;
            button.disabled = true;

            const isSmallButton = button.classList.contains('rounded-full');
            if (isSmallButton) {
                button.classList.add('opacity-80', 'cursor-not-allowed', 'text-[#e9922c]');
                button.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>`;
            } else {
                const width = button.offsetWidth;
                button.style.width = `${width}px`; 
                button.classList.add('opacity-80', 'cursor-not-allowed');
                button.innerHTML = `<div class="flex items-center justify-center gap-2"><i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i><span>Generating...</span></div>`;
            }
            lucide.createIcons();
        }

        function resetLoadingState(button) {
            if(!button) return;
            button.disabled = false;
            button.style.width = '';
            button.className = button.dataset.originalClasses;
            button.innerHTML = button.dataset.originalContent;
            lucide.createIcons();
        }

        function showSuccessState(button) {
            if(!button) return;
            const isSmallButton = button.classList.contains('rounded-full');
            if (isSmallButton) {
                button.classList.remove('text-gray-400', 'hover:text-[#e9922c]', 'hover:bg-orange-50');
                button.classList.add('text-green-600', 'bg-green-50');
                button.innerHTML = `<i data-lucide="check" class="w-4 h-4"></i>`;
            } else {
                button.className = "w-full flex items-center justify-center gap-2 px-4 py-2 bg-green-600 text-white font-semibold rounded-lg transition-all duration-300 shadow-sm";
                button.innerHTML = `<div class="flex items-center justify-center gap-2"><i data-lucide="check-circle" class="w-4 h-4"></i><span>Downloaded</span></div>`;
            }
            lucide.createIcons();
            if (typeof showToast === 'function') {
                showToast('Report generated successfully!', 'success', true);
            }
            setTimeout(() => { resetLoadingState(button); }, 2000);
        }

        // Main report generator
        async function generateReport(templateType) {
            const button = event.target.closest('.generate-btn');
            const card = button.closest('.report-template-card');
            const projectId = '<?php echo $selected_project_id; ?>';
            const projectName = '<?php echo addslashes($current_project_name); ?>';
            const projectCode = '<?php echo addslashes($current_project_code); ?>';
            const userName = '<?php echo addslashes($userName); ?>';

            if (!projectId) {
                showToast('Please select a project first', 'error');
                return;
            }

            // Get optional month selector values
            let selectedMonth = '';
            if (templateType === 'attendance-summary' && card) {
                const monthSelector = card.querySelector('.month-selector');
                selectedMonth = monthSelector ? monthSelector.value : '';
            }
            if (templateType === 'payroll-report' && card) {
                const monthSelector = card.querySelector('.payroll-month-selector');
                selectedMonth = monthSelector ? monthSelector.value : '';
            }

            setLoadingState(button);

            try {
                // Fetch data from API
                const response = await fetch(`api/reports.php?action=generate&type=${templateType}&project_id=${projectId}&month=${selectedMonth}`);
                const result = await response.json();
                
                if (!result.success) throw new Error(result.message || 'Failed to fetch data');

                const { jsPDF } = window.jspdf;
                const doc = new jsPDF(templateType === 'payroll-report' ? 'l' : 'p', 'mm', 'a4');
                const data = result.data;

                // Generate PDF based on template type
                switch (templateType) {
                    case 'employee-directory':
                        generateEmployeeDirectoryPDF(doc, data, projectName, projectCode, userName);
                        break;
                    case 'attendance-summary':
                        generateAttendanceSummaryPDF(doc, data, projectName, projectCode, userName, selectedMonth);
                        break;
                    case 'assignment-report':
                        generateAssignmentReportPDF(doc, data, projectName, projectCode, userName);
                        break;
                    case 'payroll-report':
                        generatePayrollReportPDF(doc, data, projectName, projectCode, userName, selectedMonth);
                        break;
                    case 'workforce-analytics':
                        generateWorkforceAnalyticsPDF(doc, data, projectName, projectCode, userName);
                        break;
                }

                showSuccessState(button);
            } catch (error) {
                console.error('Generation Error:', error);
                showToast('Failed: ' + error.message, 'error');
                resetLoadingState(button);
            }
        }

        // PDF Header Helper
        function addPDFHeader(doc, title, projectName, projectCode, userName) {
            const pageWidth = doc.internal.pageSize.getWidth();
            const currentDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

            doc.setFillColor(233, 146, 44);
            doc.rect(pageWidth / 2 - 6, 10, 12, 12, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(10);
            doc.text('I', pageWidth / 2, 18, { align: 'center' });

            doc.setTextColor(0, 0, 0);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text('ICMIS - Integrated Construction Management Information System', pageWidth / 2, 28, { align: 'center' });
            
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text(title.toUpperCase(), pageWidth / 2, 36, { align: 'center' });

            doc.setFontSize(9);
            doc.setFont('helvetica', 'normal');
            doc.setDrawColor(200, 200, 200);
            doc.rect(14, 42, pageWidth - 28, 20);
            
            doc.setFont('helvetica', 'bold');
            doc.text('Project:', 18, 50);
            doc.text('Date:', 120, 50);
            doc.setFont('helvetica', 'normal');
            doc.text(projectName + ' (' + projectCode + ')', 35, 50);
            doc.text(currentDate, 135, 50);
            
            doc.setFont('helvetica', 'bold');
            doc.text('Generated by:', 18, 56);
            doc.setFont('helvetica', 'normal');
            doc.text(userName, 50, 56);

            return 70;
        }

        // PDF Footer Helper
        function addPDFFooter(doc, userName) {
            const pageCount = doc.internal.getNumberOfPages();
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.setTextColor(100, 100, 100);
                doc.text(`Page ${i} of ${pageCount}`, pageWidth / 2, pageHeight - 10, { align: 'center' });
            }
        }

        // Employee Directory PDF
        function generateEmployeeDirectoryPDF(doc, data, projectName, projectCode, userName) {
            let startY = addPDFHeader(doc, 'Employee Directory', projectName, projectCode, userName);

            const tableData = data.employees.map(emp => [
                emp.employee_code,
                emp.first_name + ' ' + emp.last_name,
                emp.email || '-',
                emp.phone || '-',
                emp.role || '-',
                emp.status
            ]);

            doc.autoTable({
                startY: startY,
                head: [['Code', 'Name', 'Email', 'Phone', 'Role', 'Status']],
                body: tableData,
                theme: 'striped',
                headStyles: { fillColor: [233, 146, 44] },
                styles: { fontSize: 8 }
            });

            addPDFFooter(doc, userName);
            doc.save('employee_directory.pdf');
        }

        // Attendance Summary PDF
        function generateAttendanceSummaryPDF(doc, data, projectName, projectCode, userName, month) {
            let startY = addPDFHeader(doc, 'Attendance Summary - ' + month, projectName, projectCode, userName);

            const tableData = data.attendance.map(att => [
                att.employee_code,
                att.employee_name,
                att.present_days,
                att.absent_days,
                att.leave_days,
                att.total_hours + 'h',
                att.attendance_rate + '%'
            ]);

            doc.autoTable({
                startY: startY,
                head: [['Code', 'Name', 'Present', 'Absent', 'Leave', 'Hours', 'Rate']],
                body: tableData,
                theme: 'striped',
                headStyles: { fillColor: [34, 197, 94] },
                styles: { fontSize: 8 }
            });

            addPDFFooter(doc, userName);
            doc.save('attendance_summary_' + month + '.pdf');
        }

        // Assignment Report PDF
        function generateAssignmentReportPDF(doc, data, projectName, projectCode, userName) {
            let startY = addPDFHeader(doc, 'Assignment Report', projectName, projectCode, userName);

            const tableData = data.assignments.map(a => [
                a.employee_name,
                a.project_name,
                a.phase_name || '-',
                a.role || '-',
                a.start_date || '-',
                a.end_date || '-',
                a.status
            ]);

            doc.autoTable({
                startY: startY,
                head: [['Employee', 'Project', 'Phase', 'Role', 'Start Date', 'End Date', 'Status']],
                body: tableData,
                theme: 'striped',
                headStyles: { fillColor: [249, 115, 22] },
                styles: { fontSize: 8 }
            });

            addPDFFooter(doc, userName);
            doc.save('assignment_report.pdf');
        }

        // Payroll Report PDF
        function generatePayrollReportPDF(doc, data, projectName, projectCode, userName, month) {
            let startY = addPDFHeader(doc, 'Payroll Report - ' + month, projectName, projectCode, userName);

            const tableData = data.payroll.map(p => [
                p.employee_code,
                p.employee_name,
                p.days_worked,
                '₱' + parseFloat(p.daily_rate).toLocaleString(),
                '₱' + parseFloat(p.gross_pay).toLocaleString(),
                '₱' + parseFloat(p.deductions).toLocaleString(),
                '₱' + parseFloat(p.net_pay).toLocaleString()
            ]);

            doc.autoTable({
                startY: startY,
                head: [['Code', 'Name', 'Days', 'Rate', 'Gross', 'Deductions', 'Net Pay']],
                body: tableData,
                foot: [['', '', '', 'TOTAL', 
                    '₱' + parseFloat(data.totals.gross).toLocaleString(),
                    '₱' + parseFloat(data.totals.deductions).toLocaleString(),
                    '₱' + parseFloat(data.totals.net).toLocaleString()
                ]],
                theme: 'striped',
                headStyles: { fillColor: [147, 51, 234] },
                footStyles: { fillColor: [249, 250, 251], textColor: [0, 0, 0], fontStyle: 'bold' },
                styles: { fontSize: 8 }
            });

            addPDFFooter(doc, userName);
            doc.save('payroll_report_' + month + '.pdf');
        }

        // Workforce Analytics PDF
        function generateWorkforceAnalyticsPDF(doc, data, projectName, projectCode, userName) {
            let startY = addPDFHeader(doc, 'Workforce Analytics', projectName, projectCode, userName);

            // Summary stats
            doc.setFontSize(10);
            doc.setFont('helvetica', 'bold');
            doc.text('Summary Statistics', 14, startY);
            startY += 6;

            doc.setFont('helvetica', 'normal');
            doc.text(`Total Employees: ${data.stats.total_employees}`, 14, startY);
            doc.text(`Active Employees: ${data.stats.active_employees}`, 80, startY);
            startY += 5;
            doc.text(`Total Assignments: ${data.stats.total_assignments}`, 14, startY);
            doc.text(`Average Attendance Rate: ${data.stats.avg_attendance}%`, 80, startY);
            startY += 10;

            // Status breakdown
            doc.setFont('helvetica', 'bold');
            doc.text('Status Breakdown', 14, startY);
            startY += 6;

            const statusData = Object.entries(data.status_breakdown).map(([status, count]) => [status, count]);
            doc.autoTable({
                startY: startY,
                head: [['Status', 'Count']],
                body: statusData,
                theme: 'striped',
                headStyles: { fillColor: [20, 184, 166] },
                styles: { fontSize: 8 },
                tableWidth: 80
            });

            addPDFFooter(doc, userName);
            doc.save('workforce_analytics.pdf');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
