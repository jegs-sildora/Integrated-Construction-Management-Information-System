<?php
/**
 * Workforce Dashboard
 * Location: /modules/workforce/dashboard.php
 */

// 1. Connection & Context
include __DIR__ . '/project_context.php';
$conn = getWorkforceConnection();

// Get selected project ID from global context
$selected_project_id = getProjectContext($conn);

// Fetch all projects for dropdown
$sql_projects = "SELECT project_id, project_code, project_name FROM icmis_projects ORDER BY project_id DESC";
$result_projects = $conn->query($sql_projects);
$projects = [];
if ($result_projects && $result_projects->num_rows > 0) {
    while ($row = $result_projects->fetch_assoc()) {
        $projects[] = $row;
    }
}

// 2. Fetch Dashboard Statistics
$stats = [
    'total_employees' => 0,
    'active_employees' => 0,
    'assigned_employees' => 0,
    'on_leave' => 0,
    'attendance_rate' => 0,
    'inactive_employees' => 0
];

// Determine project filter clause
$project_filter = ($selected_project_id > 0) ? "AND wa.project_id = $selected_project_id" : "";

// A. Employee Counts
$sql_emp = "
    SELECT 
        COUNT(DISTINCT e.employee_id) as total,
        COUNT(DISTINCT CASE WHEN e.status = 'Active' THEN e.employee_id END) as active,
        COUNT(DISTINCT CASE WHEN e.status = 'On Leave' THEN e.employee_id END) as on_leave,
        COUNT(DISTINCT CASE WHEN e.status = 'Terminated' OR e.status = 'Resigned' THEN e.employee_id END) as inactive
    FROM workforce_employees e
    LEFT JOIN workforce_assignments wa ON e.employee_id = wa.employee_id
    WHERE 1=1 $project_filter
";
$res_emp = $conn->query($sql_emp);
if ($row = $res_emp->fetch_assoc()) {
    $stats['total_employees'] = $row['total'];
    $stats['active_employees'] = $row['active'];
    $stats['on_leave'] = $row['on_leave'];
    $stats['inactive_employees'] = $row['inactive'];
}

// Count employees who have at least one assignment (optionally filtered by project)
$sql_assigned = "SELECT COUNT(DISTINCT employee_id) as assigned FROM workforce_assignments WHERE 1=1 " . ($selected_project_id > 0 ? "AND project_id = $selected_project_id" : "");
$res_assigned = $conn->query($sql_assigned);
$stats['assigned_employees'] = ($res_assigned && ($rassign = $res_assigned->fetch_assoc())) ? intval($rassign['assigned']) : 0;

// Ensure "Total Personnel" shows the total number of active employees
$sql_total_active = "SELECT COUNT(DISTINCT employee_id) as total_active FROM workforce_employees WHERE status = 'Active'";
$res_total_active = $conn->query($sql_total_active);
$stats['total_employees'] = ($res_total_active && ($rt = $res_total_active->fetch_assoc())) ? intval($rt['total_active']) : $stats['total_employees'];

// B. Attendance Rate (Today)
$today = date('Y-m-d');

// Use all active employees from `workforce_employees` as the denominator
$sql_active = "SELECT COUNT(DISTINCT employee_id) as active_count FROM workforce_employees WHERE status = 'Active'";
$res_active = $conn->query($sql_active);
$active_count = ($res_active && ($r = $res_active->fetch_assoc())) ? intval($r['active_count']) : 0;
$active_count = $active_count > 0 ? $active_count : 1; // Prevent div by zero

$sql_att = "
    SELECT COUNT(DISTINCT employee_id) as present 
    FROM workforce_attendance 
    WHERE attendance_date = '$today' AND status = 'Present' 
    " . ($selected_project_id > 0 ? "AND project_id = $selected_project_id" : "");
    
$res_att = $conn->query($sql_att);
$present_count = ($res_att->fetch_assoc())['present'] ?? 0;
$raw_rate = ($present_count / $active_count) * 100;
$stats['attendance_rate'] = round(min(100, $raw_rate), 1);

// D. On Leave / Absent (based on today's attendance records)
$sql_onleave = "
    SELECT COUNT(DISTINCT employee_id) as on_leave_count
    FROM workforce_attendance
    WHERE attendance_date = '$today' AND status IN ('On Leave','Absent')
    " . ($selected_project_id > 0 ? "AND project_id = $selected_project_id" : "");
$res_onleave = $conn->query($sql_onleave);
$stats['on_leave'] = ($res_onleave && ($r_on = $res_onleave->fetch_assoc())) ? intval($r_on['on_leave_count']) : 0;

// C. Recent Activity Logs
$logs = [];
$log_sql = "
    SELECT al.action, al.details, al.created_at, u.full_name as username
    FROM icmis_audit_logs al
    LEFT JOIN icmis_users u ON al.user_id = u.user_id
    WHERE al.module = 'WORKFORCE'
    ORDER BY al.created_at DESC LIMIT 5
";
$log_res = $conn->query($log_sql);
if ($log_res) {
    while($r = $log_res->fetch_assoc()) $logs[] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workforce Dashboard | ICMIS</title>
    
    <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased">

    <?php 
        include __DIR__ . '/../../includes/sidebar.php';
        include __DIR__ . '/../../includes/toast.php';
        include __DIR__ . '/../../includes/header.php';
    ?>

    <main class="ml-56 mt-16 p-8 transition-all duration-300 animate-fade-in">
        <div class="max-w-[90rem] mx-auto">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-gray-900">Workforce Overview</h1>
                    <p class="text-sm text-gray-500 mt-1">Real-time metrics and manpower management.</p>
                </div>

                <div class="bg-white p-1.5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-2">
                    <span class="pl-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Project:</span>
                    <select id="projectSelector" 
                            onchange="window.location.href='dashboard.php?project_id=' + this.value" 
                            class="bg-gray-50 border-0 text-gray-700 text-sm font-bold rounded-lg focus:ring-2 focus:ring-[#e9922c] block p-2 pr-8 cursor-pointer min-w-[200px]">
                        <option value="0">All Projects (Global View)</option>
                        <?php foreach($projects as $p): ?>
                            <option value="<?= $p['project_id'] ?>" <?= $selected_project_id == $p['project_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['project_code'] . ' - ' . $p['project_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-4 gap-6 mb-8">
                
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                            <i data-lucide="users" class="w-5 h-5"></i>
                        </div>
                        <span class="flex items-center text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded-full">
                            <i data-lucide="trending-up" class="w-3 h-3 mr-1"></i> Active
                        </span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Personnel</p>
                        <h3 class="text-3xl font-black text-gray-900"><?= number_format($stats['total_employees'] ?? 0) ?></h3>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-[#e9922c] to-[#d97706] p-6 rounded-xl shadow-lg text-white relative overflow-hidden">
                    <div class="absolute right-0 top-0 opacity-10 transform translate-x-2 -translate-y-2">
                        <i data-lucide="hard-hat" class="w-24 h-24"></i>
                    </div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center text-white">
                            <i data-lucide="activity" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <div class="space-y-1 relative z-10">
                        <p class="text-sm font-bold text-white/80 uppercase tracking-wide">Currently Active</p>
                        <h3 class="text-3xl font-black text-white"><?= number_format($stats['active_employees'] ?? 0) ?></h3>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center text-green-600">
                            <i data-lucide="calendar-check" class="w-5 h-5"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-400"><?= date('M d') ?></span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Attendance Rate</p>
                        <div class="flex items-baseline gap-2">
                            <h3 class="text-3xl font-black text-gray-900"><?= $stats['attendance_rate'] ?>%</h3>
                            <span class="text-sm text-gray-400">Present</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center text-[#e9922c]">
                            <i data-lucide="clock" class="w-5 h-5"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-400"><?= date('M d') ?></span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">On Leave / Absent</p>
                        <h3 class="text-3xl font-black text-gray-900"><?= number_format($stats['on_leave'] ?? 0) ?></h3>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                
                <div class="lg:col-span-2 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-gray-900 flex items-center gap-2">
                            <i data-lucide="bar-chart-2" class="w-5 h-5 text-gray-400"></i> Personnel Status Distribution
                        </h3>
                    </div>
                    <div class="h-64 relative">
                        <canvas id="workforceStatusChart"></canvas>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col">
                    <h3 class="font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <i data-lucide="zap" class="w-5 h-5 text-[#e9922c]"></i> Quick Actions
                    </h3>
                    
                    <div class="grid grid-cols-1 gap-3 flex-1">
                        <a href="employees.php?action=add" class="flex items-center p-3 rounded-lg border border-gray-100 hover:border-[#e9922c] hover:bg-orange-50 transition-all group">
                            <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center group-hover:bg-[#e9922c] group-hover:text-white transition-colors">
                                <i data-lucide="user-plus" class="w-5 h-5"></i>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-bold text-gray-900">Add Employee</h4>
                                <p class="text-xs text-gray-500">Register new personnel</p>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 ml-auto group-hover:text-[#e9922c]"></i>
                        </a>

                        <a href="attendance.php" class="flex items-center p-3 rounded-lg border border-gray-100 hover:border-[#e9922c] hover:bg-orange-50 transition-all group">
                            <div class="w-10 h-10 rounded-full bg-green-50 text-green-600 flex items-center justify-center group-hover:bg-[#e9922c] group-hover:text-white transition-colors">
                                <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-bold text-gray-900">Log Attendance</h4>
                                <p class="text-xs text-gray-500">Daily time tracking</p>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 ml-auto group-hover:text-[#e9922c]"></i>
                        </a>

                        <a href="payroll.php" class="flex items-center p-3 rounded-lg border border-gray-100 hover:border-[#e9922c] hover:bg-orange-50 transition-all group">
                            <div class="w-10 h-10 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center group-hover:bg-[#e9922c] group-hover:text-white transition-colors">
                                <i data-lucide="banknote" class="w-5 h-5"></i>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-bold text-gray-900">Process Payroll</h4>
                                <p class="text-xs text-gray-500">Generate computations</p>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 ml-auto group-hover:text-[#e9922c]"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-gray-900 flex items-center gap-2">
                        <i data-lucide="history" class="w-4 h-4 text-gray-400"></i> Recent Workforce Activity
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-white text-xs uppercase text-gray-500 font-bold border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-3">Action</th>
                                <th class="px-6 py-3">User</th>
                                <th class="px-6 py-3">Details</th>
                                <th class="px-6 py-3 text-right">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <?php if (count($logs) > 0): ?>
                                <?php foreach($logs as $log): 
                                    $actionClass = 'bg-gray-100 text-gray-600';
                                    if(strpos($log['action'], 'CREATE') !== false) $actionClass = 'bg-green-50 text-green-700 border-green-100';
                                    if(strpos($log['action'], 'UPDATE') !== false) $actionClass = 'bg-blue-50 text-blue-700 border-blue-100';
                                    if(strpos($log['action'], 'DELETE') !== false) $actionClass = 'bg-red-50 text-red-700 border-red-100';
                                ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-1 rounded text-[10px] font-bold border <?= $actionClass ?>">
                                            <?= htmlspecialchars($log['action']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 font-medium text-gray-900">
                                        <?= htmlspecialchars($log['username'] ?? 'System') ?>
                                    </td>
                                    <td class="px-6 py-3 text-gray-500 font-mono text-xs truncate max-w-xs">
                                        <?= htmlspecialchars(mb_strimwidth($log['details'], 0, 50, "...")) ?>
                                    </td>
                                    <td class="px-6 py-3 text-right text-gray-400 text-xs">
                                        <?= date('M d, H:i', strtotime($log['created_at'])) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <i data-lucide="inbox" class="w-8 h-8 text-gray-200"></i>
                                            <p>No recent activity found.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Init Icons
        lucide.createIcons();

        // Init Chart
        const ctx = document.getElementById('workforceStatusChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Active', 'On Leave', 'Inactive'],
                datasets: [{
                    label: 'Personnel Count',
                    data: [
                        <?= $stats['active_employees'] ?>, 
                        <?= $stats['on_leave'] ?>, 
                        <?= $stats['inactive_employees'] ?>
                    ],
                    backgroundColor: [
                        '#e9922c', // Active (Brand Orange)
                        '#3b82f6', // Leave (Blue)
                        '#9ca3af'  // Inactive (Gray)
                    ],
                    borderRadius: 6,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { borderDash: [2, 4], color: '#f3f4f6' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    </script>
</body>
</html>