<?php
// ============================================================
// ATTENDANCE MANAGEMENT
// ============================================================

include __DIR__ . '/project_context.php';
$conn = getWorkforceConnection();

$selected_project_id = getProjectContext($conn);

// Fetch all projects for dropdown
$projects = [];
$sql_projects = "SELECT project_id, project_code, project_name FROM icmis_projects ORDER BY project_id DESC";
$result_projects = $conn->query($sql_projects);
if ($result_projects) {
    while ($row = $result_projects->fetch_assoc()) {
        $projects[] = $row;
    }
}

// Determine Current Project Name
$selected_project_name = "All Projects";
if ($selected_project_id > 0) {
    foreach ($projects as $proj) {
        if ($proj['project_id'] == $selected_project_id) {
            $selected_project_name = $proj['project_name'];
            break;
        }
    }
}

// Build breadcrumb
$current_page = basename($_SERVER['PHP_SELF']);
$breadcrumbHTML = '<div class="flex items-center gap-2 text-sm">';
$breadcrumbHTML .= '<div class="relative inline-block">';
$breadcrumbHTML .= '<select id="projectSelector" onchange="window.location.href=\'' . $current_page . '?project_id=\' + this.value" class="appearance-none bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] pl-3 pr-8 py-1.5 hover:bg-gray-50 transition-colors cursor-pointer font-medium">';
$breadcrumbHTML .= '<option value="0">All Projects</option>';
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

$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// --- 1. STATISTICS ---
$stats = ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0];

// Count Total Active Employees (Always Count ALL)
$countSql = "SELECT COUNT(*) as total FROM workforce_employees WHERE status = 'Active'";
$stmt = $conn->prepare($countSql);
$stmt->execute();
$stats['total'] = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Count Statuses for the Selected Date (and optionally Project)
$projectFilter = ($selected_project_id > 0) ? " AND project_id = $selected_project_id" : "";
$statSql = "SELECT 
    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
    SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late
    FROM workforce_attendance WHERE attendance_date = ? $projectFilter";
$stmt = $conn->prepare($statSql);
$stmt->bind_param("s", $selectedDate);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stats['present'] = $res['present'] ?? 0;
$stats['absent'] = $res['absent'] ?? 0;
$stats['late'] = $res['late'] ?? 0;
$stmt->close();

// --- 2. INDIVIDUAL EMPLOYEES (Fetch ALL) ---
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$totalPages = ceil($stats['total'] / $limit);

$individual_employees = [];

// Query: Fetch ALL active employees, Left join Attendance
// We do NOT join workforce_assignments, so we see everyone.
$sql = "SELECT e.employee_id, e.employee_code, e.first_name, e.last_name, 
        jt.title_name as job_title,
        a.attendance_id, a.time_in, a.time_out, a.status as attendance_status, a.remarks
        FROM workforce_employees e
        LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
        -- Join attendance for this specific date
        LEFT JOIN workforce_attendance a ON e.employee_id = a.employee_id AND a.attendance_date = ?
        WHERE e.status = 'Active' 
        ORDER BY e.last_name ASC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $selectedDate, $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $individual_employees[] = $row;
$stmt->close();


// --- 3. GROUPED EMPLOYEES ---
$groups_data = [];
$gSql = "SELECT g.group_id, g.group_name, g.group_code,
         CONCAT(l.first_name, ' ', l.last_name) as leader_name,
         e.employee_id, e.first_name, e.last_name, e.employee_code,
         jt.title_name as job_title,
         att.status as attendance_status, att.time_in, att.time_out, att.remarks
         FROM workforce_employee_groups g
         LEFT JOIN workforce_employees l ON g.group_leader_id = l.employee_id
         JOIN workforce_group_memberships gm ON g.group_id = gm.group_id
         JOIN workforce_employees e ON gm.employee_id = e.employee_id
         LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
         LEFT JOIN workforce_attendance att ON e.employee_id = att.employee_id 
              AND att.attendance_date = ? 
         WHERE e.status = 'Active'
         ORDER BY g.group_name, e.last_name";

$stmt = $conn->prepare($gSql);
$stmt->bind_param("s", $selectedDate);
$stmt->execute();
$gRes = $stmt->get_result();

while ($row = $gRes->fetch_assoc()) {
    $gid = $row['group_id'];
    if (!isset($groups_data[$gid])) {
        $groups_data[$gid] = [
            'info' => [
                'name' => $row['group_name'],
                'code' => $row['group_code'],
                'leader' => $row['leader_name']
            ],
            'members' => [],
            'stats' => ['total' => 0, 'present' => 0]
        ];
    }
    $groups_data[$gid]['members'][] = $row;
    $groups_data[$gid]['stats']['total']++;
    if ($row['attendance_status'] === 'Present') {
        $groups_data[$gid]['stats']['present']++;
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
    <title>Attendance | ICMIS</title>
    <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .employee-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #e9922c, #f59e0b); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px; }
        .tab-btn.active { border-color: #e9922c; color: #e9922c; }
        .tab-btn.inactive { border-color: transparent; color: #6b7280; }
        .tab-btn.inactive:hover { color: #374151; }
        .group-details { transition: max-height 0.3s ease-in-out; max-height: 0; overflow: hidden; }
        .group-details.open { max-height: 2000px; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <?php include __DIR__ . '/../../includes/sidebar.php'; include __DIR__ . '/../../includes/toast.php'; include __DIR__ . '/../../includes/header.php'; ?>
    
    <main class="ml-56 mt-16 p-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-1 mb-6 border-b border-gray-200">
                <button onclick="switchView('individual')" id="btn-individual" class="tab-btn active px-6 py-3 text-sm font-semibold border-b-2 transition-colors">Individual Attendance</button>
                <button onclick="switchView('groups')" id="btn-groups" class="tab-btn inactive px-6 py-3 text-sm font-semibold border-b-2 transition-colors">Group Attendance</button>
            </div>
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Attendance Tracker</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage daily logs for <span class="font-semibold text-[#e9922c]"><?php echo htmlspecialchars($selected_project_name); ?></span></p>
                </div>
                <div class="flex items-center gap-3 bg-white p-2 rounded-xl shadow-sm border border-gray-200">
                    <div class="relative">
                        <i data-lucide="calendar" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="date" id="attendanceDate" value="<?php echo $selectedDate; ?>" onchange="updateDate(this.value)" class="pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium focus:ring-2 focus:ring-[#e9922c] focus:border-transparent outline-none">
                    </div>
                    <div class="h-6 w-px bg-gray-200"></div>
                    <button onclick="saveAllAttendance()" class="flex items-center gap-2 bg-[#e9922c] text-white px-5 py-2 rounded-lg hover:bg-[#d17f1f] transition-all shadow-sm font-medium text-sm"><i data-lucide="save" class="w-4 h-4"></i> Save Changes</button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center"><i data-lucide="users" class="w-5 h-5"></i></div>
                    <div><p class="text-xs font-medium text-gray-500">Total Active</p><h3 class="text-xl font-black text-gray-900"><?php echo $stats['total']; ?></h3></div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-green-50 text-green-600 flex items-center justify-center"><i data-lucide="user-check" class="w-5 h-5"></i></div>
                    <div><p class="text-xs font-medium text-gray-500">Present</p><h3 class="text-xl font-black text-gray-900"><?php echo $stats['present']; ?></h3></div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-red-50 text-red-600 flex items-center justify-center"><i data-lucide="user-x" class="w-5 h-5"></i></div>
                    <div><p class="text-xs font-medium text-gray-500">Absent</p><h3 class="text-xl font-black text-gray-900"><?php echo $stats['absent']; ?></h3></div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-yellow-50 text-yellow-600 flex items-center justify-center"><i data-lucide="clock" class="w-5 h-5"></i></div>
                    <div><p class="text-xs font-medium text-gray-500">Late</p><h3 class="text-xl font-black text-gray-900"><?php echo $stats['late']; ?></h3></div>
                </div>
            </div>

            <div id="view-individual">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden min-h-[400px]">
                    <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                        <h3 class="font-semibold text-gray-700">All Employees List</h3>
                        <div class="flex items-center gap-4">
                             <span class="text-xs text-gray-500">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                            <button onclick="markAllPresent('individual')" class="px-3 py-1.5 bg-amber-50 text-amber-600 text-xs font-bold rounded-lg hover:bg-amber-100 border border-amber-200 hover:underline">Mark All Present</button>
                        </div>
                    </div>
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Employee</th>
                                <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Role</th>
                                <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase w-32">Status</th>
                                <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase w-32">Time In</th>
                                <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase w-32">Time Out</th>
                                <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($individual_employees)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                                <i data-lucide="user-x" class="w-6 h-6 text-gray-400"></i>
                                            </div>
                                            <p class="text-gray-900 font-medium mb-1">No active employees found.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($individual_employees as $emp): 
                                    $initials = strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1));
                                    $status = $emp['attendance_status'];
                                    $rowClass = $status === 'Absent' ? 'bg-red-50/50' : ($status === 'Present' ? 'bg-green-50/30' : '');
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors attendance-row <?php echo $rowClass; ?>" data-id="<?php echo $emp['employee_id']; ?>">
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="employee-avatar shrink-0"><?php echo $initials; ?></div>
                                            <div>
                                                <p class="font-bold text-gray-900 text-sm"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></p>
                                                <p class="text-xs text-gray-500 font-bold"><?php echo htmlspecialchars($emp['employee_code']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 font-bold text-gray-600 text-center text-sm"><?php echo htmlspecialchars($emp['job_title'] ?? '—'); ?></td>
                                    <td class="px-6 py-3 w-20 font-bold text-center"><?php echo renderStatusSelect($status); ?></td>
                                    <td class="px-6 py-3 font-bold"><?php echo renderTimeInput('time_in', $emp['time_in'], $status); ?></td>
                                    <td class="px-6 py-3 font-bold"><?php echo renderTimeInput('time_out', $emp['time_out'], $status); ?></td>
                                    <td class="px-6 py-3 font-bold"><input type="text" name="remarks" value="<?php echo htmlspecialchars($emp['remarks'] ?? ''); ?>" oninput="saveToLocal(this)" class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-sm outline-none focus:border-[#e9922c]"></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                        <div class="text-sm text-gray-500">Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $limit, $stats['total']); ?></span> of <span class="font-medium"><?php echo $stats['total']; ?></span></div>
                        <div class="flex items-center gap-2">
                            <div class="flex gap-1">
                                <?php if ($page > 1): ?>
                                    <a href="?date=<?php echo $selectedDate; ?>&project_id=<?php echo $selected_project_id; ?>&page=<?php echo $page - 1; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium hover:bg-gray-50">Prev</a>
                                <?php else: ?>
                                    <span class="px-3 py-1 text-sm text-gray-400">Prev</span>
                                <?php endif; ?>
                            </div>

                            <div class="hidden sm:flex items-center gap-1">
                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <?php if ($p == $page): ?>
                                        <span class="px-3 py-1 bg-[#e9922c] text-white rounded-md text-sm font-medium"><?php echo $p; ?></span>
                                    <?php else: ?>
                                        <a href="?date=<?php echo $selectedDate; ?>&project_id=<?php echo $selected_project_id; ?>&page=<?php echo $p; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium hover:bg-gray-50"><?php echo $p; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>

                            <div class="flex gap-1">
                                <?php if ($page < $totalPages): ?>
                                    <a href="?date=<?php echo $selectedDate; ?>&project_id=<?php echo $selected_project_id; ?>&page=<?php echo $page + 1; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium hover:bg-gray-50">Next</a>
                                <?php else: ?>
                                    <span class="px-3 py-1 text-sm text-gray-400">Next</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="view-groups" class="hidden space-y-4">
                <?php if (empty($groups_data)): ?>
                    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4"><i data-lucide="users-2" class="w-8 h-8 text-gray-400"></i></div>
                        <h3 class="text-lg font-bold text-gray-900">No Deployment Groups</h3>
                        <p class="text-gray-500 mb-6">Create groups to manage attendance by teams.</p>
                        <a href="employee_groups.php" class="text-[#e9922c] font-medium hover:underline">Go to Deployment Groups &rarr;</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($groups_data as $gid => $group): 
                        $percent = ($group['stats']['total'] > 0) ? round(($group['stats']['present'] / $group['stats']['total']) * 100) : 0;
                    ?>
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden group-card" id="group-<?php echo $gid; ?>">
                        <div class="p-4 flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors" onclick="toggleGroup(<?php echo $gid; ?>)">
                            <div class="flex items-center gap-4">
                                <div class="bg-orange-100 p-3 rounded-lg text-[#e9922c]"><i data-lucide="hard-hat" class="w-6 h-6"></i></div>
                                <div><h4 class="font-bold text-gray-900 text-lg"><?php echo htmlspecialchars($group['info']['name']); ?></h4><p class="text-xs text-gray-500">Leader: <?php echo htmlspecialchars($group['info']['leader'] ?? 'None'); ?></p></div>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="w-32 hidden sm:block">
                                    <div class="flex justify-between text-xs mb-1"><span class="text-gray-500">Attendance</span><span class="font-bold text-gray-700"><?php echo $group['stats']['present']; ?>/<?php echo $group['stats']['total']; ?></span></div>
                                    <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden"><div class="h-full bg-green-500" style="width: <?php echo $percent; ?>%"></div></div>
                                </div>
                                <button onclick="event.stopPropagation(); markGroupPresent(<?php echo $gid; ?>)" class="px-3 py-1.5 bg-amber-50 text-amber-600 text-xs font-bold rounded-lg hover:bg-amber-100 border border-amber-200">Mark Team</button>
                                <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 transform transition-transform" id="icon-<?php echo $gid; ?>"></i>
                            </div>
                        </div>
                        <div class="group-details bg-gray-50 border-t border-gray-200" id="details-<?php echo $gid; ?>">
                            <div class="p-2">
                                <table class="w-full bg-white rounded-lg border border-gray-200 overflow-hidden">
                                    <thead class="bg-gray-100 border-b border-gray-200">
                                        <tr><th class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase text-left">Member</th><th class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase text-left w-32">Status</th><th class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase text-left w-28">Time In</th><th class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase text-left w-28">Time Out</th><th class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase text-left">Remarks</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <?php foreach ($group['members'] as $m): $mStatus = $m['attendance_status']; ?>
                                        <tr class="attendance-row group-row-<?php echo $gid; ?>" data-id="<?php echo $m['employee_id']; ?>">
                                            <td class="px-4 py-2 text-sm font-medium text-gray-700"><?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?></td>
                                            <td class="px-4 py-2"><?php echo renderStatusSelect($mStatus); ?></td>
                                            <td class="px-4 py-2"><?php echo renderTimeInput('time_in', $m['time_in'], $mStatus); ?></td>
                                            <td class="px-4 py-2"><?php echo renderTimeInput('time_out', $m['time_out'], $mStatus); ?></td>
                                            <td class="px-4 py-2"><input type="text" name="remarks" value="<?php echo htmlspecialchars($m['remarks'] ?? ''); ?>" oninput="saveToLocal(this)" class="w-full px-2 py-1 border border-gray-200 rounded text-xs outline-none"></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php 
    function renderStatusSelect($current) { 
        $options = ['Present'=>'text-green-600','Late'=>'text-yellow-600','Absent'=>'text-red-600','On Leave'=>'text-blue-600']; 
        $html = '<select name="status" onchange="handleStatusChange(this); saveToLocal(this)" class="w-26 px-2 py-1.5 border border-gray-200 rounded-lg text-sm font-medium focus:ring-2 focus:ring-[#e9922c] outline-none"><option value="" class="text-gray-400">Select...</option>'; 
        foreach($options as $val=>$class) { 
            $sel=($current===$val)?'selected':''; 
            $html.="<option value='$val' $sel class='$class'>$val</option>"; 
        } 
        $html.='</select>'; 
        return $html; 
    }
    
    function renderTimeInput($name, $val, $status) { 
        $disabled = ($status === 'Absent' || $status === 'On Leave') ? 'disabled' : ''; 
        return "<input type='time' name='$name' value='$val' oninput='saveToLocal(this)' class='w-full px-2 py-1.5 border border-gray-200 rounded-lg text-sm focus:border-blue-500 outline-none disabled:bg-gray-100 disabled:text-gray-400' $disabled>"; 
    } 
    ?>

    <script>
        window.SELECTED_PROJECT_ID = <?php echo json_encode($selected_project_id); ?>;
    </script>
    
    <script src="js/attendance.js"></script>
</body>
</html>