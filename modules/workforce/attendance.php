<?php
// ============================================================
// ALL PHP LOGIC MUST BE BEFORE ANY HTML OUTPUT
// ============================================================

include __DIR__ . '/project_context.php';
$conn = getWorkforceConnection();

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
$breadcrumbHTML .= '</div></div>';

$pageSection = "Labor & Workforce";
$pageTitle = "Attendance Management";
$pageSubTitle = $breadcrumbHTML;

// Get selected date (default to today)
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get attendance stats for selected date
$stats = [
    'total' => 0,
    'present' => 0,
    'absent' => 0,
    'late' => 0
];

$result = $conn->query("SELECT COUNT(*) as total FROM workforce_employees WHERE status = 'Active'");
if ($result) $stats['total'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT 
    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
    SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late
    FROM workforce_attendance WHERE attendance_date = '$selectedDate'");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['present'] = $row['present'] ?: 0;
    $stats['absent'] = $row['absent'] ?: 0;
    $stats['late'] = $row['late'] ?: 0;
}

// Fetch employees with their attendance for selected date
$employees = [];
$sql = "SELECT e.employee_id, e.employee_code, e.first_name, e.last_name, e.status as emp_status,
        a.attendance_id, a.time_in, a.time_out, a.status as attendance_status, a.remarks,
        wa.project_id, p.project_name
        FROM workforce_employees e
        LEFT JOIN workforce_attendance a ON e.employee_id = a.employee_id AND a.attendance_date = ?
        LEFT JOIN workforce_assignments wa ON e.employee_id = wa.employee_id AND wa.status = 'Active'
        LEFT JOIN icmis_projects p ON wa.project_id = p.project_id
        WHERE e.status = 'Active'
        ORDER BY e.last_name, e.first_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $selectedDate);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}
$stmt->close();

$userName = $_SESSION['user_name'] ?? "Admin";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management | ICMIS</title>
    
    <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        .employee-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e9922c, #f59e0b);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php 
        include __DIR__ . '/../../includes/sidebar.php';
        include __DIR__ . '/../../includes/toast.php';
        include __DIR__ . '/../../includes/header.php'; 
    ?>

    <main class="ml-56 mt-16 p-6">
        <div class="max-w-7xl mx-auto">
            
            <!-- Page Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl text-gray-900 font-bold">Attendance Management</h1>
                    <p class="text-sm text-gray-500 mt-1">Track and manage employee attendance</p>
                </div>
                <div class="flex items-center gap-3">
                    <input type="date" id="attendanceDate" value="<?php echo $selectedDate; ?>" 
                           onchange="window.location.href='attendance.php?date=' + this.value"
                           class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none">
                    <button onclick="saveAllAttendance()" class="flex items-center gap-2 bg-[#e9922c] text-white px-6 py-2.5 rounded-lg hover:bg-[#d17f1f] transition-colors duration-200 shadow-sm">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Save Attendance
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="users" class="w-5 h-5 text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Total Employees</p>
                            <h3 class="text-xl font-bold text-gray-900"><?php echo $stats['total']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="user-check" class="w-5 h-5 text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Present</p>
                            <h3 class="text-xl font-bold text-gray-900"><?php echo $stats['present']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="user-x" class="w-5 h-5 text-red-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Absent</p>
                            <h3 class="text-xl font-bold text-gray-900"><?php echo $stats['absent']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="clock" class="w-5 h-5 text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Late</p>
                            <h3 class="text-xl font-bold text-gray-900"><?php echo $stats['late']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Employee</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Project</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Time In</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Time Out</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($employees as $emp): 
                            $initials = strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1));
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors attendance-row" data-employee-id="<?php echo $emp['employee_id']; ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="employee-avatar"><?php echo $initials; ?></div>
                                    <div>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></p>
                                        <p class="text-xs text-gray-500">#<?php echo htmlspecialchars($emp['employee_code']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($emp['project_name'] ?? 'Unassigned'); ?></td>
                            <td class="px-6 py-4">
                                <input type="time" name="time_in" value="<?php echo $emp['time_in'] ?? ''; ?>" 
                                       class="px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none text-sm">
                            </td>
                            <td class="px-6 py-4">
                                <input type="time" name="time_out" value="<?php echo $emp['time_out'] ?? ''; ?>" 
                                       class="px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none text-sm">
                            </td>
                            <td class="px-6 py-4">
                                <select name="status" class="px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none text-sm">
                                    <option value="">-- Select --</option>
                                    <option value="Present" <?php echo ($emp['attendance_status'] === 'Present') ? 'selected' : ''; ?>>Present</option>
                                    <option value="Absent" <?php echo ($emp['attendance_status'] === 'Absent') ? 'selected' : ''; ?>>Absent</option>
                                    <option value="Late" <?php echo ($emp['attendance_status'] === 'Late') ? 'selected' : ''; ?>>Late</option>
                                    <option value="On Leave" <?php echo ($emp['attendance_status'] === 'On Leave') ? 'selected' : ''; ?>>On Leave</option>
                                </select>
                            </td>
                            <td class="px-6 py-4">
                                <input type="text" name="remarks" value="<?php echo htmlspecialchars($emp['remarks'] ?? ''); ?>" 
                                       placeholder="Optional remarks"
                                       class="px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none text-sm w-full">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i data-lucide="calendar" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                                <p>No employees found</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <script>
        lucide.createIcons();

        function saveAllAttendance() {
            const date = document.getElementById('attendanceDate').value;
            const projectId = <?php echo $selected_project_id; ?>;
            const rows = document.querySelectorAll('.attendance-row');
            const records = [];

            rows.forEach(row => {
                const employeeId = row.dataset.employeeId;
                const timeIn = row.querySelector('input[name="time_in"]').value;
                const timeOut = row.querySelector('input[name="time_out"]').value;
                const status = row.querySelector('select[name="status"]').value;
                const remarks = row.querySelector('input[name="remarks"]').value;

                if (status) {
                    records.push({
                        employee_id: parseInt(employeeId),
                        time_in: timeIn || null,
                        time_out: timeOut || null,
                        status: status,
                        remarks: remarks
                    });
                }
            });

            if (records.length === 0) {
                showToast('Please select status for at least one employee', 'warning');
                return;
            }

            fetch('api/attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'save_bulk', 
                    project_id: projectId,
                    date: date,
                    records: records 
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Attendance saved successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Error saving attendance', 'error');
                }
            })
            .catch(err => {
                showToast('Error saving attendance', 'error');
            });
        }
    </script>
</body>
</html>
