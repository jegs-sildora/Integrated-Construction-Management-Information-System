<?php
/**
 * Payroll Management - Workforce Module
 * 
 * Manages employee payroll records with project context
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

// Fetch all projects for dropdown
$projects = [];
$result = $conn->query("SELECT project_id, project_code, project_name FROM icmis_projects ORDER BY project_id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
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

// Get payroll period (default current month)
$payroll_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$start_date = $payroll_month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

// Get employees assigned to this project with their attendance
$employees = [];
$payroll_data = [];

if ($selected_project_id) {
    // Get assigned employees with their job titles for daily rate
    // DB Schema: workforce_employees (employee_id, employee_code, user_id, job_title_id, first_name, last_name, email, phone, status, hire_date)
    // DB Schema: workforce_job_titles (job_title_id, title_name, department, description, default_daily_rate, is_active)
    // DB Schema: workforce_assignments (assignment_id, employee_id, project_id, phase_id, role, task_description, start_date, end_date, status)
    $sql = "SELECT DISTINCT e.employee_id, e.employee_code, e.first_name, e.last_name, 
                   a.role, e.status, jt.default_daily_rate, jt.title_name as job_title
            FROM workforce_employees e
            JOIN workforce_assignments a ON e.employee_id = a.employee_id
            LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
            WHERE a.project_id = ? AND a.status = 'Active' AND e.status = 'Active'
            ORDER BY e.last_name, e.first_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    $stmt->close();
    
    // Calculate payroll for each employee
    foreach ($employees as $emp) {
        // Get attendance counts for the period
        // DB Schema: workforce_attendance (attendance_id, employee_id, project_id, attendance_date, time_in, time_out, status, remarks)
        // status ENUM: 'Present','Absent','Late','On Leave'
        $att_sql = "SELECT 
                        COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_days,
                        COUNT(CASE WHEN status = 'Late' THEN 1 END) as late_days,
                        COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent_days,
                        COUNT(CASE WHEN status = 'On Leave' THEN 1 END) as leave_days,
                        SUM(CASE WHEN time_in IS NOT NULL AND time_out IS NOT NULL 
                            THEN TIMESTAMPDIFF(HOUR, time_in, time_out) ELSE 0 END) as total_hours
                    FROM workforce_attendance 
                    WHERE employee_id = ? AND project_id = ? 
                    AND attendance_date BETWEEN ? AND ?";
        $stmt = $conn->prepare($att_sql);
        $stmt->bind_param("iiss", $emp['employee_id'], $selected_project_id, $start_date, $end_date);
        $stmt->execute();
        $att_result = $stmt->get_result();
        $attendance = $att_result->fetch_assoc();
        $stmt->close();
        
        // Get daily rate from job title (default to 800 if not set)
        $daily_rate = floatval($emp['default_daily_rate'] ?? 800);
        
        // Calculate basic payroll (daily rate * days worked)
        // Late days count as full days for payroll purposes
        $present_days = ($attendance['present_days'] ?? 0) + ($attendance['late_days'] ?? 0);
        $gross_pay = $present_days * $daily_rate;
        $deductions = $gross_pay * 0.05; // 5% deductions placeholder (SSS, PhilHealth, PagIBIG)
        $net_pay = $gross_pay - $deductions;
        
        $payroll_data[] = [
            'employee_id' => $emp['employee_id'],
            'employee_code' => $emp['employee_code'],
            'first_name' => $emp['first_name'],
            'last_name' => $emp['last_name'],
            'role' => $emp['role'],
            'job_title' => $emp['job_title'] ?? 'N/A',
            'present_days' => $present_days,
            'absent_days' => $attendance['absent_days'] ?? 0,
            'leave_days' => $attendance['leave_days'] ?? 0,
            'total_hours' => $attendance['total_hours'] ?? 0,
            'daily_rate' => $daily_rate,
            'gross_pay' => $gross_pay,
            'deductions' => $deductions,
            'net_pay' => $net_pay
        ];
    }
}

// Calculate totals
$total_gross = array_sum(array_column($payroll_data, 'gross_pay'));
$total_deductions = array_sum(array_column($payroll_data, 'deductions'));
$total_net = array_sum(array_column($payroll_data, 'net_pay'));

// Set page variables
$pageSection = "Labor & Workforce";
$pageTitle = "Payroll";
$pageSubTitle = $breadcrumbHTML;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll | Workforce | ICMIS</title>
    <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
    <style>
        @media print {
            @page { margin: 0.5in; size: landscape; }
            body { background-color: white !important; }
            header, aside, .sidebar, .no-print { display: none !important; }
            main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            .print-only { display: block !important; }
            table { font-size: 10pt !important; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php 
        include __DIR__ . '/../../includes/sidebar.php';
        include __DIR__ . '/../../includes/toast.php';
        include __DIR__ . '/../../includes/header.php'; 
    ?>

    <!-- Print Header (hidden on screen) -->
    <div class="print-only hidden mb-6">
        <div class="text-center border-b pb-4 mb-4">
            <h1 class="text-2xl font-bold uppercase">Payroll Report</h1>
            <p class="text-sm text-gray-500">ICMIS - Labor & Workforce Management</p>
            <p class="text-sm font-bold mt-1 text-[#e9922c]"><?php echo htmlspecialchars($current_project_name); ?></p>
            <p class="text-xs mt-1">Period: <?php echo date('F Y', strtotime($payroll_month)); ?></p>
        </div>
    </div>

    <main class="ml-56 mt-16 p-6">
        <div class="max-w-7xl mx-auto">
            
            <!-- Page Header -->
            <div class="border-b border-gray-200 pb-6 mb-8 no-print">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl text-gray-900 font-bold">Payroll Management</h1>
                        <p class="text-sm text-gray-500 mt-1">Monthly payroll for <span class="font-semibold text-[#e9922c]"><?php echo htmlspecialchars($current_project_name); ?></span></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <!-- Month Selector -->
                        <input type="month" id="payrollMonth" value="<?php echo $payroll_month; ?>" 
                               onchange="window.location.href='?project_id=<?php echo $selected_project_id; ?>&month=' + this.value"
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none">
                        <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <i data-lucide="printer" class="w-4 h-4"></i>
                            Print
                        </button>
                        <button onclick="exportPayrollPDF()" class="flex items-center gap-2 px-4 py-2 bg-[#e9922c] text-white rounded-lg hover:bg-[#d17f1f] transition-colors">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            Export PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 no-print">
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-500">Total Employees</span>
                        <span class="p-2 bg-blue-100 rounded-lg">
                            <i data-lucide="users" class="w-5 h-5 text-blue-600"></i>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900"><?php echo count($payroll_data); ?></p>
                    <p class="text-xs text-gray-500 mt-1">Active this period</p>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-500">Gross Pay</span>
                        <span class="p-2 bg-green-100 rounded-lg">
                            <i data-lucide="banknote" class="w-5 h-5 text-green-600"></i>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900">₱<?php echo number_format($total_gross, 2); ?></p>
                    <p class="text-xs text-gray-500 mt-1">Before deductions</p>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-500">Total Deductions</span>
                        <span class="p-2 bg-red-100 rounded-lg">
                            <i data-lucide="minus-circle" class="w-5 h-5 text-red-600"></i>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900">₱<?php echo number_format($total_deductions, 2); ?></p>
                    <p class="text-xs text-gray-500 mt-1">Taxes & contributions</p>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-500">Net Pay</span>
                        <span class="p-2 bg-orange-100 rounded-lg">
                            <i data-lucide="wallet" class="w-5 h-5 text-orange-600"></i>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-[#e9922c]">₱<?php echo number_format($total_net, 2); ?></p>
                    <p class="text-xs text-gray-500 mt-1">Total payable</p>
                </div>
            </div>

            <!-- Payroll Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center no-print">
                    <div class="flex items-center gap-3">
                        <h3 class="font-bold text-gray-800">Payroll Details</h3>
                        <span class="text-xs font-medium text-gray-500 bg-white border border-gray-200 px-2 py-0.5 rounded-full">
                            <?php echo date('F Y', strtotime($payroll_month)); ?>
                        </span>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                            <tr>
                                <th class="px-6 py-3">Employee</th>
                                <th class="px-6 py-3">Role</th>
                                <th class="px-6 py-3 text-center">Days Worked</th>
                                <th class="px-6 py-3 text-center">Absences</th>
                                <th class="px-6 py-3 text-right">Daily Rate</th>
                                <th class="px-6 py-3 text-right">Gross Pay</th>
                                <th class="px-6 py-3 text-right">Deductions</th>
                                <th class="px-6 py-3 text-right">Net Pay</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <?php if (empty($payroll_data)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <i data-lucide="file-x" class="w-12 h-12 text-gray-300 mb-3"></i>
                                        <p class="font-medium">No payroll data found</p>
                                        <p class="text-xs text-gray-400 mt-1">No employees assigned to this project for the selected period</p>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($payroll_data as $row): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-sm font-bold">
                                            <?php echo strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></p>
                                            <p class="text-xs text-gray-500">#<?php echo htmlspecialchars($row['employee_code']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($row['role'] ?? '-'); ?></td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-semibold text-green-600"><?php echo $row['present_days']; ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-semibold text-red-600"><?php echo $row['absent_days']; ?></span>
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-gray-600">₱<?php echo number_format($row['daily_rate'], 2); ?></td>
                                <td class="px-6 py-4 text-right font-mono font-medium">₱<?php echo number_format($row['gross_pay'], 2); ?></td>
                                <td class="px-6 py-4 text-right font-mono text-red-600">-₱<?php echo number_format($row['deductions'], 2); ?></td>
                                <td class="px-6 py-4 text-right font-mono font-bold text-[#e9922c]">₱<?php echo number_format($row['net_pay'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <!-- Totals Row -->
                            <tr class="bg-gray-50 font-bold">
                                <td colspan="5" class="px-6 py-4 text-right uppercase text-gray-600">Total</td>
                                <td class="px-6 py-4 text-right font-mono">₱<?php echo number_format($total_gross, 2); ?></td>
                                <td class="px-6 py-4 text-right font-mono text-red-600">-₱<?php echo number_format($total_deductions, 2); ?></td>
                                <td class="px-6 py-4 text-right font-mono text-[#e9922c]">₱<?php echo number_format($total_net, 2); ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- Print Footer -->
    <div class="print-only print-footer hidden mt-12">
        <div class="grid grid-cols-3 gap-8">
            <div class="text-center">
                <p class="text-xs font-bold text-gray-500 uppercase mb-8">Prepared By:</p>
                <div class="border-b border-black w-3/4 mx-auto"></div>
                <p class="text-sm font-bold mt-2 pt-4"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></p>
                <p class="text-xs text-gray-500">HR Officer</p>
            </div>
            <div class="text-center">
                <p class="text-xs font-bold text-gray-500 uppercase mb-8">Verified By:</p>
                <div class="border-b border-black w-3/4 mx-auto"></div>
                <p class="text-sm font-bold mt-2 pt-4">___________</p> 
                <p class="text-xs text-gray-500">Project Engineer</p>
            </div>
            <div class="text-center">
                <p class="text-xs font-bold text-gray-500 uppercase mb-8">Approved By:</p>
                <div class="border-b border-black w-3/4 mx-auto"></div>
                <p class="text-sm font-bold mt-2 pt-4">___________</p> 
                <p class="text-xs text-gray-500">Project Manager</p>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script>
        lucide.createIcons();

        function exportPayrollPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a4'); // Landscape

            const projectName = '<?php echo addslashes($current_project_name); ?>';
            const period = '<?php echo date('F Y', strtotime($payroll_month)); ?>';
            
            // Header
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text('PAYROLL REPORT', 148, 15, { align: 'center' });
            
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text('ICMIS - Labor & Workforce Management', 148, 22, { align: 'center' });
            doc.text(`Project: ${projectName}`, 148, 28, { align: 'center' });
            doc.text(`Period: ${period}`, 148, 34, { align: 'center' });

            // Table
            const tableData = <?php echo json_encode(array_map(function($row) {
                return [
                    $row['employee_code'],
                    $row['first_name'] . ' ' . $row['last_name'],
                    $row['role'] ?? '-',
                    $row['present_days'],
                    $row['absent_days'],
                    '₱' . number_format($row['daily_rate'], 2),
                    '₱' . number_format($row['gross_pay'], 2),
                    '₱' . number_format($row['deductions'], 2),
                    '₱' . number_format($row['net_pay'], 2)
                ];
            }, $payroll_data)); ?>;

            doc.autoTable({
                startY: 42,
                head: [['Code', 'Employee Name', 'Role', 'Days', 'Absences', 'Rate', 'Gross', 'Deductions', 'Net Pay']],
                body: tableData,
                foot: [['', '', '', '', 'TOTAL:', '', 
                    '₱<?php echo number_format($total_gross, 2); ?>', 
                    '₱<?php echo number_format($total_deductions, 2); ?>', 
                    '₱<?php echo number_format($total_net, 2); ?>']],
                theme: 'striped',
                headStyles: { fillColor: [233, 146, 44] },
                footStyles: { fillColor: [249, 250, 251], textColor: [0, 0, 0], fontStyle: 'bold' },
                styles: { fontSize: 8 }
            });

            doc.save(`payroll_${period.replace(' ', '_')}.pdf`);
            showToast('Payroll PDF exported successfully!', 'success');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
