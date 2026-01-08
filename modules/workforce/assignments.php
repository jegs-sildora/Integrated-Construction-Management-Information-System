<?php
// ============================================================
// ASSIGNMENTS MANAGEMENT (Unified View)
// ============================================================

include __DIR__ . '/project_context.php';
$conn = getWorkforceConnection();

$selected_project_id = getProjectContext($conn);

// 1. Fetch Context Data
// Projects for Context Selector
$projects = [];
$p_res = $conn->query("SELECT project_id, project_code, project_name FROM icmis_projects ORDER BY project_id DESC");
if ($p_res) while ($row = $p_res->fetch_assoc()) $projects[] = $row;

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

// 2. Fetch Dropdown Data (Employees, Groups, Phases)
$employees = [];
$e_res = $conn->query("SELECT employee_id, first_name, last_name, employee_code FROM workforce_employees WHERE status = 'Active' ORDER BY last_name ASC");
if ($e_res) while($r = $e_res->fetch_assoc()) $employees[] = $r;

$groups = [];
$g_res = $conn->query("SELECT group_id, group_name FROM workforce_employee_groups ORDER BY group_name ASC");
if ($g_res) while($r = $g_res->fetch_assoc()) $groups[] = $r;

// 3. Statistics (Context Aware)
$stats = ['total' => 0, 'active' => 0, 'completed' => 0, 'cancelled' => 0];
$statWhere = $selected_project_id > 0 ? "WHERE project_id = $selected_project_id" : "";

$s_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM workforce_assignments $statWhere";
$s_res = $conn->query($s_sql);
if($s_res) $stats = $s_res->fetch_assoc();

// 4. Assignments List (Paginated & Context Aware)
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = "WHERE 1=1";
if ($selected_project_id > 0) {
    $whereClause .= " AND wa.project_id = $selected_project_id";
}

// Count Total
$countSql = "SELECT COUNT(*) as total FROM workforce_assignments wa $whereClause";
$t_res = $conn->query($countSql);
$totalRows = $t_res->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Fetch Data
$assignments = [];
$sql = "SELECT wa.*, 
        e.first_name, e.last_name, e.employee_code,
        p.project_name, p.project_code,
        ph.phase_name
        FROM workforce_assignments wa
        JOIN workforce_employees e ON wa.employee_id = e.employee_id
        JOIN icmis_projects p ON wa.project_id = p.project_id
        LEFT JOIN icmis_project_phases ph ON wa.phase_id = ph.phase_id
        $whereClause
        ORDER BY wa.status ASC, wa.start_date DESC
        LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);
if ($result) while($row = $result->fetch_assoc()) $assignments[] = $row;

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
$pageTitle = "Assignments";
$pageSubTitle = $breadcrumbHTML;
$userName = $_SESSION['user_name'] ?? "Admin";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments | ICMIS</title>
    <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .employee-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, #e9922c, #f59e0b);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 600; font-size: 14px;
        }
        .modal-content { transform: scale(0.95); opacity: 0; transition: all 0.2s ease-out; }
        .modal-open .modal-content { transform: scale(1); opacity: 1; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <?php 
        include __DIR__ . '/../../includes/sidebar.php';
        include __DIR__ . '/../../includes/toast.php';
        include __DIR__ . '/../../includes/header.php'; 
    ?>

    <main class="ml-56 mt-16 p-8">
        <div class="max-w-7xl mx-auto">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Resource Allocation</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage project assignments for <span class="font-semibold text-[#e9922c]"><?php echo htmlspecialchars($selected_project_name); ?></span></p>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="openBulkModal()" class="flex items-center gap-2 bg-white border border-gray-300 text-gray-700 px-4 py-2.5 rounded-lg hover:bg-gray-50 transition-all shadow-sm font-medium text-sm">
                        <i data-lucide="layers" class="w-4 h-4"></i>
                        Bulk Assign Group
                    </button>
                    <button onclick="openModal()" class="flex items-center gap-2 bg-[#e9922c] text-white px-5 py-2.5 rounded-lg hover:bg-[#d17f1f] transition-all shadow-sm font-medium text-sm">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        New Assignment
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center"><i data-lucide="clipboard-list" class="w-5 h-5"></i></div>
                    <div><p class="text-xs font-medium text-gray-500 uppercase">Total</p><h3 class="text-xl font-bold text-gray-900"><?php echo $stats['total']; ?></h3></div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-green-50 text-green-600 flex items-center justify-center"><i data-lucide="check-circle" class="w-5 h-5"></i></div>
                    <div><p class="text-xs font-medium text-gray-500 uppercase">Active</p><h3 class="text-xl font-bold text-gray-900"><?php echo $stats['active']; ?></h3></div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center"><i data-lucide="check-check" class="w-5 h-5"></i></div>
                    <div><p class="text-xs font-medium text-gray-500 uppercase">Completed</p><h3 class="text-xl font-bold text-gray-900"><?php echo $stats['completed']; ?></h3></div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-red-50 text-red-600 flex items-center justify-center"><i data-lucide="x-circle" class="w-5 h-5"></i></div>
                    <div><p class="text-xs font-medium text-gray-500 uppercase">Cancelled</p><h3 class="text-xl font-bold text-gray-900"><?php echo $stats['cancelled']; ?></h3></div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm min-h-[400px]">
                <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-700">Assignment List</h3>
                    <div class="flex items-center gap-4">
                        <span class="text-xs text-gray-500">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                    </div>
                </div>
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Employee</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Project & Phase</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Role & Task</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Dates</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($assignments)): ?>
                            <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">No assignments found for this project context.</td></tr>
                        <?php else: ?>
                            <?php foreach ($assignments as $a): 
                                $initials = strtoupper(substr($a['first_name'], 0, 1) . substr($a['last_name'], 0, 1));
                                $statusClass = match($a['status']) {
                                    'Active' => 'bg-green-100 text-green-700',
                                    'Completed' => 'bg-blue-100 text-blue-700',
                                    'Cancelled' => 'bg-red-100 text-red-700',
                                    default => 'bg-gray-100 text-gray-700'
                                };
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="employee-avatar shrink-0"><?php echo $initials; ?></div>
                                        <div>
                                            <p class="font-bold text-gray-900 text-sm"><?php echo htmlspecialchars($a['first_name'] . ' ' . $a['last_name']); ?></p>
                                            <p class="text-xs text-gray-500 font-mono"><?php echo htmlspecialchars($a['employee_code']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($a['project_name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($a['phase_name'] ?? 'No Phase'); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900"><?php echo htmlspecialchars($a['role'] ?? '-'); ?></p>
                                    <p class="text-xs text-gray-500 truncate max-w-[150px]"><?php echo htmlspecialchars($a['task_description'] ?? ''); ?></p>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600">
                                    <div class="flex items-center gap-1"><i data-lucide="calendar" class="w-3 h-3"></i> <?php echo date('M d, Y', strtotime($a['start_date'])); ?></div>
                                    <div class="flex items-center gap-1 mt-1"><i data-lucide="arrow-right" class="w-3 h-3 text-gray-400"></i> <?php echo $a['end_date'] ? date('M d, Y', strtotime($a['end_date'])) : 'Ongoing'; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($a['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button onclick="editAssignment(<?php echo $a['assignment_id']; ?>)" class="p-1.5 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                                        <button onclick="deleteAssignment(<?php echo $a['assignment_id']; ?>)" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        Showing page <span class="font-medium"><?php echo $page; ?></span> of <span class="font-medium"><?php echo $totalPages; ?></span>
                    </div>
                    <div class="flex gap-1">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&project_id=<?php echo $selected_project_id; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium hover:bg-gray-50">Prev</a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&project_id=<?php echo $selected_project_id; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium hover:bg-gray-50">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div id="assignmentModal" class="hidden fixed inset-0 z-50 overflow-hidden">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full flex flex-col max-h-[90vh] modal-content">
                <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-2xl">
                    <h3 class="text-lg font-bold text-gray-900" id="modalTitle">New Assignment</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>
                <form id="assignmentForm" onsubmit="event.preventDefault(); saveAssignment();" class="p-6 space-y-4 overflow-y-auto">
                    <input type="hidden" name="assignment_id" id="assignment_id">
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Employee</label>
                        <select name="employee_id" id="employee_id" required class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#e9922c] outline-none text-sm">
                            <option value="">Select Employee...</option>
                            <?php foreach($employees as $e): ?>
                                <option value="<?php echo $e['employee_id']; ?>"><?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Project</label>
                            <select name="project_id" id="project_id" required onchange="fetchPhases('phase_id', this.value)" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#e9922c] outline-none text-sm">
                                <option value="">Select Project...</option>
                                <?php foreach($projects as $p): ?>
                                    <option value="<?php echo $p['project_id']; ?>"><?php echo htmlspecialchars($p['project_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Phase</label>
                            <select name="phase_id" id="phase_id" disabled class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#e9922c] outline-none text-sm disabled:text-gray-400">
                                <option value="">Select Project First</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Role</label>
                        <input type="text" name="role" id="role" placeholder="Role on Project" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl outline-none text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Start Date</label>
                            <input type="date" name="start_date" id="start_date" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl outline-none text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl outline-none text-sm">
                        </div>
                    </div>

                    <button type="submit" class="w-full px-4 py-3 bg-[#e9922c] text-white rounded-xl hover:bg-[#d17f1f] font-bold shadow-sm">Save Assignment</button>
                </form>
            </div>
        </div>
    </div>

    <div id="bulkModal" class="hidden fixed inset-0 z-50 overflow-hidden">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeBulkModal()"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full flex flex-col max-h-[90vh] modal-content">
                <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-2xl">
                    <h3 class="text-lg font-bold text-gray-900">Bulk Group Assignment</h3>
                    <button onclick="closeBulkModal()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>
                <form id="bulkForm" onsubmit="event.preventDefault(); saveBulkAssignment();" class="p-6 space-y-4">
                    <div class="bg-blue-50 border border-blue-100 p-3 rounded-lg text-xs text-blue-700 mb-2">
                        <i data-lucide="info" class="w-3 h-3 inline mr-1"></i>
                        This will create individual assignment records for every member of the selected group.
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Select Group</label>
                        <select name="group_id" id="bulk_group_id" required class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#e9922c] outline-none text-sm">
                            <option value="">Choose a Deployment Group...</option>
                            <?php foreach($groups as $g): ?>
                                <option value="<?php echo $g['group_id']; ?>"><?php echo htmlspecialchars($g['group_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Target Project</label>
                            <select name="project_id" id="bulk_project_id" required onchange="fetchPhases('bulk_phase_id', this.value)" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#e9922c] outline-none text-sm">
                                <option value="">Select Project...</option>
                                <?php foreach($projects as $p): ?>
                                    <option value="<?php echo $p['project_id']; ?>"><?php echo htmlspecialchars($p['project_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Phase</label>
                            <select name="phase_id" id="bulk_phase_id" disabled class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#e9922c] outline-none text-sm disabled:text-gray-400">
                                <option value="">Select Project First</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Start Date</label>
                            <input type="date" name="start_date" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl outline-none text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">End Date</label>
                            <input type="date" name="end_date" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl outline-none text-sm">
                        </div>
                    </div>

                    <button type="submit" id="bulkBtn" class="w-full px-4 py-3 bg-[#e9922c] text-white rounded-xl hover:bg-[#d17f1f] font-bold shadow-sm">
                        Deploy Team
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // --- Modals ---
        function openModal() {
            document.getElementById('assignmentModal').classList.remove('hidden');
            setTimeout(() => document.querySelector('#assignmentModal .modal-content').classList.add('modal-open'), 10);
            
            // Auto-select current project context
            const currentProj = <?php echo $selected_project_id; ?>;
            if(currentProj > 0) {
                const sel = document.getElementById('project_id');
                sel.value = currentProj;
                fetchPhases('phase_id', currentProj);
            }
        }
        function closeModal() {
            document.querySelector('#assignmentModal .modal-content').classList.remove('modal-open');
            setTimeout(() => document.getElementById('assignmentModal').classList.add('hidden'), 200);
        }

        function openBulkModal() {
            document.getElementById('bulkModal').classList.remove('hidden');
            setTimeout(() => document.querySelector('#bulkModal .modal-content').classList.add('modal-open'), 10);
            
            const currentProj = <?php echo $selected_project_id; ?>;
            if(currentProj > 0) {
                document.getElementById('bulk_project_id').value = currentProj;
                fetchPhases('bulk_phase_id', currentProj);
            }
        }
        function closeBulkModal() {
            document.querySelector('#bulkModal .modal-content').classList.remove('modal-open');
            setTimeout(() => document.getElementById('bulkModal').classList.add('hidden'), 200);
        }

        // --- Helpers ---
        async function fetchPhases(targetId, projectId) {
            const select = document.getElementById(targetId);
            select.innerHTML = '<option>Loading...</option>';
            select.disabled = true;
            try {
                const res = await fetch(`api/assignments.php?action=get_phases&project_id=${projectId}`);
                const data = await res.json();
                select.innerHTML = '<option value="">-- No Phase --</option>';
                if(data.success && data.data) {
                    data.data.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.phase_id;
                        opt.textContent = p.phase_name;
                        select.appendChild(opt);
                    });
                    select.disabled = false;
                }
            } catch(e) { console.error(e); }
        }

        // --- CRUD Logic ---
        async function saveAssignment() {
            const form = document.getElementById('assignmentForm');
            const formData = new FormData(form);
            const payload = Object.fromEntries(formData);
            payload.action = payload.assignment_id ? 'update' : 'create';

            try {
                const res = await fetch('api/assignments.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if(data.success) {
                    showToast('Saved successfully', 'success');
                    location.reload();
                } else alert(data.message);
            } catch(e) { alert('System error'); }
        }

        async function editAssignment(id) {
            try {
                const res = await fetch(`api/assignments.php?action=get&id=${id}`);
                const data = await res.json();
                if(data.success) {
                    const a = data.data;
                    document.getElementById('assignment_id').value = a.assignment_id;
                    document.getElementById('employee_id').value = a.employee_id;
                    document.getElementById('project_id').value = a.project_id;
                    await fetchPhases('phase_id', a.project_id);
                    document.getElementById('phase_id').value = a.phase_id;
                    document.getElementById('role').value = a.role;
                    document.getElementById('start_date').value = a.start_date;
                    document.getElementById('end_date').value = a.end_date;
                    openModal();
                    document.getElementById('modalTitle').textContent = "Edit Assignment";
                }
            } catch(e) { alert('Error fetching details'); }
        }

        async function deleteAssignment(id) {
            if(!confirm("Delete this assignment?")) return;
            try {
                const res = await fetch('api/assignments.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'delete', id: id})
                });
                const data = await res.json();
                if(data.success) location.reload();
                else alert(data.message);
            } catch(e) { alert('System error'); }
        }

        // --- Bulk Logic (The "Unified" Magic) ---
        async function saveBulkAssignment() {
            const btn = document.getElementById('bulkBtn');
            const originalText = btn.innerText;
            btn.innerText = "Deploying...";
            btn.disabled = true;

            const groupId = document.getElementById('bulk_group_id').value;
            const projectId = document.getElementById('bulk_project_id').value;
            const phaseId = document.getElementById('bulk_phase_id').value;
            const startDate = document.querySelector('#bulkForm [name="start_date"]').value;
            const endDate = document.querySelector('#bulkForm [name="end_date"]').value;

            try {
                // 1. Get Group Members
                const groupRes = await fetch(`api/employee_groups.php?action=get&id=${groupId}`);
                const groupData = await groupRes.json();
                
                if(!groupData.success || !groupData.data.members.length) {
                    throw new Error("Group has no members or not found");
                }

                // 2. Create Assignment for Each Member
                // We map fetch promises to run them in parallel
                const promises = groupData.data.members.map(empId => {
                    return fetch('api/assignments.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            action: 'create',
                            employee_id: empId,
                            project_id: projectId,
                            phase_id: phaseId,
                            start_date: startDate,
                            end_date: endDate,
                            status: 'Active'
                        })
                    });
                });

                await Promise.all(promises);
                
                showToast(`Team deployed successfully!`, 'success');
                setTimeout(() => location.reload(), 1000);

            } catch(e) {
                alert(e.message || "Error during bulk deployment");
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>