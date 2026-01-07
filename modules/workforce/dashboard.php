<?php
// ============================================================
// ALL PHP LOGIC MUST BE BEFORE ANY HTML OUTPUT
// ============================================================

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

// Build breadcrumb navigation with dropdown
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

// Set Header Variables
$pageSection = "Labor & Workforce";
$pageTitle = "Workforce Dashboard";
$pageSubTitle = $breadcrumbHTML;

// Get stats for dashboard
$stats = [
    'total_employees' => 0,
    'active_employees' => 0,
    'inactive_employees' => 0,
    'on_leave' => 0,
    'attendance_today' => 0,
    'present_today' => 0,
    'absent_today' => 0,
    'total_assignments' => 0,
    'active_assignments' => 0
];

// Total Employees
$result = $conn->query("SELECT COUNT(*) as total FROM workforce_employees");
if ($result) {
    $stats['total_employees'] = $result->fetch_assoc()['total'];
}

// Active Employees
$result = $conn->query("SELECT COUNT(*) as total FROM workforce_employees WHERE status = 'Active'");
if ($result) {
    $stats['active_employees'] = $result->fetch_assoc()['total'];
}

// Inactive Employees (includes Terminated)
$result = $conn->query("SELECT COUNT(*) as total FROM workforce_employees WHERE status IN ('Inactive', 'Terminated')");
if ($result) {
    $stats['inactive_employees'] = $result->fetch_assoc()['total'];
}

// On Leave - check attendance for On Leave status since workforce_employees doesn't have 'On Leave'
// workforce_employees.status ENUM: 'Active','Inactive','Terminated'
// workforce_attendance.status ENUM: 'Present','Absent','Late','On Leave'
$result = $conn->query("SELECT COUNT(DISTINCT employee_id) as total FROM workforce_attendance 
    WHERE attendance_date = CURDATE() AND status = 'On Leave'");
if ($result) {
    $stats['on_leave'] = $result->fetch_assoc()['total'];
}

// Attendance Today
$today = date('Y-m-d');
$result = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent
    FROM workforce_attendance WHERE attendance_date = '$today'");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['attendance_today'] = $row['total'] ?: 0;
    $stats['present_today'] = $row['present'] ?: 0;
    $stats['absent_today'] = $row['absent'] ?: 0;
}

// Attendance rate
$attendance_rate = $stats['total_employees'] > 0 
    ? round(($stats['present_today'] / $stats['total_employees']) * 100) 
    : 0;

// Active Assignments
$result = $conn->query("SELECT COUNT(*) as total FROM workforce_assignments WHERE status = 'Active'");
if ($result) {
    $stats['active_assignments'] = $result->fetch_assoc()['total'];
}

// Total Assignments
$result = $conn->query("SELECT COUNT(*) as total FROM workforce_assignments");
if ($result) {
    $stats['total_assignments'] = $result->fetch_assoc()['total'];
}

// Workforce utilization
$utilization_rate = $stats['total_employees'] > 0 
    ? round(($stats['active_assignments'] / $stats['total_employees']) * 100) 
    : 0;

// Project status counts
$project_stats = ['planning' => 0, 'active' => 0, 'completed' => 0, 'on_hold' => 0];
$result = $conn->query("SELECT status, COUNT(*) as cnt FROM icmis_projects GROUP BY status");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $status = strtolower($row['status']);
        if ($status === 'planning') $project_stats['planning'] = $row['cnt'];
        elseif ($status === 'active' || $status === 'in progress') $project_stats['active'] = $row['cnt'];
        elseif ($status === 'completed') $project_stats['completed'] = $row['cnt'];
        elseif ($status === 'on hold') $project_stats['on_hold'] = $row['cnt'];
    }
}

// Attendance trend (last 7 days)
$attendance_trend = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $result = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present
        FROM workforce_attendance WHERE attendance_date = '$date'");
    if ($result) {
        $row = $result->fetch_assoc();
        $total = $row['total'] ?: 1;
        $present = $row['present'] ?: 0;
        $attendance_trend[] = round(($present / $total) * 100);
    } else {
        $attendance_trend[] = 0;
    }
}

$userName = $_SESSION['user_name'] ?? "Admin";
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
    
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <?php 
        include __DIR__ . '/../../includes/sidebar.php';
        include __DIR__ . '/../../includes/toast.php';
        include __DIR__ . '/../../includes/header.php'; 
    ?>

    <main class="ml-56 mt-22 p-6">
        <div class="max-w-7xl mx-auto">
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-4 gap-6 mb-8">
                <!-- Total Employees -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="users" class="w-6 h-6 text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Total Employees</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_employees']); ?></h3>
                            <p class="text-xs text-gray-400">Active: <?php echo $stats['active_employees']; ?> | Leave: <?php echo $stats['on_leave']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Workforce Utilization -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="target" class="w-6 h-6 text-purple-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Workforce Utilization</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo $utilization_rate; ?>%</h3>
                            <p class="text-xs text-gray-400">Assigned: <?php echo $stats['active_assignments']; ?> workers</p>
                        </div>
                    </div>
                </div>

                <!-- Attendance Today -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="calendar-check" class="w-6 h-6 text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Attendance Today</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo $attendance_rate; ?>%</h3>
                            <p class="text-xs text-gray-400">Present: <?php echo $stats['present_today']; ?> | Absent: <?php echo $stats['absent_today']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Active Assignments -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="clipboard-list" class="w-6 h-6 text-orange-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Active Assignments</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['active_assignments']); ?></h3>
                            <p class="text-xs text-gray-400">Total: <?php echo $stats['total_assignments']; ?> assignments</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-3 gap-6 mb-8">
                <!-- Attendance Trend -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Attendance Trend (7 Days)</h3>
                    <div class="h-64">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>

                <!-- Project Status -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Project Status</h3>
                    <div class="h-64 flex items-center justify-center">
                        <canvas id="projectChart"></canvas>
                    </div>
                </div>

                <!-- Employee Status -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Employee Status</h3>
                    <div class="h-64 flex items-center justify-center">
                        <canvas id="employeeChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Assignments</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-sm text-gray-500 border-b border-gray-200">
                                <th class="pb-3 font-medium">Employee</th>
                                <th class="pb-3 font-medium">Project</th>
                                <th class="pb-3 font-medium">Role</th>
                                <th class="pb-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody id="recentAssignments">
                            <?php
                            $sql = "SELECT wa.*, we.first_name, we.last_name, p.project_name
                                    FROM workforce_assignments wa
                                    LEFT JOIN workforce_employees we ON wa.employee_id = we.employee_id
                                    LEFT JOIN icmis_projects p ON wa.project_id = p.project_id
                                    ORDER BY wa.assignment_id DESC
                                    LIMIT 5";
                            $result = $conn->query($sql);
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $statusClass = match(strtolower($row['status'])) {
                                        'active' => 'bg-green-100 text-green-700',
                                        'completed' => 'bg-blue-100 text-blue-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                        default => 'bg-gray-100 text-gray-700'
                                    };
                                    echo '<tr class="border-b border-gray-100">';
                                    echo '<td class="py-3">' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                                    echo '<td class="py-3">' . htmlspecialchars($row['project_name'] ?? 'N/A') . '</td>';
                                    echo '<td class="py-3">' . htmlspecialchars($row['role'] ?? 'N/A') . '</td>';
                                    echo '<td class="py-3"><span class="px-2 py-1 rounded-full text-xs font-medium ' . $statusClass . '">' . htmlspecialchars($row['status']) . '</span></td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="4" class="py-4 text-center text-gray-500">No recent assignments</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Attendance Trend Chart
        new Chart(document.getElementById('attendanceChart'), {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Attendance %',
                    data: <?php echo json_encode($attendance_trend); ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 5,
                    pointBackgroundColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { min: 0, max: 100, ticks: { callback: v => v + '%' } }
                }
            }
        });

        // Project Status Chart
        new Chart(document.getElementById('projectChart'), {
            type: 'doughnut',
            data: {
                labels: ['Planning', 'In Progress', 'Completed', 'On Hold'],
                datasets: [{
                    data: [
                        <?php echo $project_stats['planning']; ?>,
                        <?php echo $project_stats['active']; ?>,
                        <?php echo $project_stats['completed']; ?>,
                        <?php echo $project_stats['on_hold']; ?>
                    ],
                    backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // Employee Status Chart
        new Chart(document.getElementById('employeeChart'), {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Inactive', 'On Leave'],
                datasets: [{
                    data: [
                        <?php echo $stats['active_employees']; ?>,
                        <?php echo $stats['inactive_employees']; ?>,
                        <?php echo $stats['on_leave']; ?>
                    ],
                    backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { position: 'bottom' } }
            }
        });
    </script>
</body>
</html>
