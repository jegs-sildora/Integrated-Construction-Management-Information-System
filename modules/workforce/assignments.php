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
$pageTitle = "Employee Assignments";
$pageSubTitle = $breadcrumbHTML;

// Get stats
$stats = [
    'total' => 0,
    'active' => 0,
    'completed' => 0,
    'cancelled' => 0
];

$result = $conn->query("SELECT COUNT(*) as total FROM workforce_assignments");
if ($result) $stats['total'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM workforce_assignments WHERE status = 'Active'");
if ($result) $stats['active'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM workforce_assignments WHERE status = 'Completed'");
if ($result) $stats['completed'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM workforce_assignments WHERE status = 'Cancelled'");
if ($result) $stats['cancelled'] = $result->fetch_assoc()['total'];

// Fetch assignments
$assignments = [];
$sql = "SELECT wa.*, 
        e.first_name, e.last_name, e.employee_code,
        p.project_name, p.project_code,
        ph.phase_name
        FROM workforce_assignments wa
        LEFT JOIN workforce_employees e ON wa.employee_id = e.employee_id
        LEFT JOIN icmis_projects p ON wa.project_id = p.project_id
        LEFT JOIN icmis_project_phases ph ON wa.phase_id = ph.phase_id
        ORDER BY wa.assignment_id DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
}

// Fetch employees for modal
$employees = [];
$result = $conn->query("SELECT employee_id, first_name, last_name, employee_code FROM workforce_employees WHERE status = 'Active' ORDER BY last_name, first_name");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}

// Fetch phases
$phases = [];
$result = $conn->query("SELECT phase_id, phase_name, project_id FROM icmis_project_phases ORDER BY phase_name");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $phases[] = $row;
    }
}

$userName = $_SESSION['user_name'] ?? "Admin";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Assignments | ICMIS</title>
    
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
            
            <!-- Tabs -->
            <div class="flex items-center gap-1 mb-6 border-b border-gray-200">
                <button onclick="switchTab('assignments')" id="tab-assignments" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-[#e9922c] text-[#e9922c] transition-colors">
                    Assignments
                </button>
                <button onclick="switchTab('group-assignments')" id="tab-group-assignments" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                    Group Assignments
                </button>
            </div>

            <!-- Page Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl text-gray-900 font-bold">Assignments</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage and track employee project assignments</p>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="openBulkModal()" class="flex items-center gap-2 bg-white border border-gray-300 text-gray-700 px-4 py-2.5 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                        <i data-lucide="users" class="w-4 h-4"></i>
                        Bulk Assignment
                    </button>
                    <button onclick="openModal()" class="flex items-center gap-2 bg-[#e9922c] text-white px-6 py-2.5 rounded-lg hover:bg-[#d17f1f] transition-colors duration-200 shadow-sm">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Add Assignment
                    </button>
                </div>
            </div>

            <!-- Tab Content: Assignments -->
            <div id="content-assignments" class="tab-content">

            <!-- Stats Cards -->
            <div class="grid grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="clipboard-list" class="w-5 h-5 text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Total Assignments</p>
                            <h3 class="text-xl font-bold text-gray-900"><?php echo $stats['total']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Active</p>
                            <h3 class="text-xl font-bold text-gray-900"><?php echo $stats['active']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="check-check" class="w-5 h-5 text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Completed</p>
                            <h3 class="text-xl font-bold text-gray-900"><?php echo $stats['completed']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="x-circle" class="w-5 h-5 text-red-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Cancelled</p>
                            <h3 class="text-xl font-bold text-gray-900"><?php echo $stats['cancelled']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="text" id="searchInput" placeholder="Search assignments..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none w-72">
                        </div>
                        <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none">
                            <option value="">Filter</option>
                            <option value="Active">Active</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <p class="text-sm text-gray-500">Showing <span id="showingCount"><?php echo count($assignments); ?></span> assignments</p>
                </div>
            </div>

            <!-- Assignments Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Employee</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Project</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Task</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Phase</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Role</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Start Date</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">End Date</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="assignmentsTableBody" class="divide-y divide-gray-100">
                        <?php foreach ($assignments as $a): 
                            $initials = strtoupper(substr($a['first_name'] ?? 'N', 0, 1) . substr($a['last_name'] ?? 'A', 0, 1));
                            $statusClass = match(strtolower($a['status'])) {
                                'active' => 'bg-green-100 text-green-700',
                                'completed' => 'bg-blue-100 text-blue-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                                default => 'bg-gray-100 text-gray-700'
                            };
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors assignment-row" 
                            data-name="<?php echo strtolower(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')); ?>"
                            data-status="<?php echo $a['status']; ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="employee-avatar"><?php echo $initials; ?></div>
                                    <div>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')); ?></p>
                                        <p class="text-xs text-gray-500">#<?php echo htmlspecialchars($a['employee_code'] ?? 'N/A'); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($a['project_name'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($a['task'] ?? '-'); ?></td>
                            <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($a['phase_name'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($a['role'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-gray-600 text-sm">
                                <?php echo $a['start_date'] ? date('M d, Y', strtotime($a['start_date'])) : 'N/A'; ?>
                            </td>
                            <td class="px-6 py-4 text-gray-600 text-sm">
                                <?php echo $a['end_date'] ? date('M d, Y', strtotime($a['end_date'])) : 'Ongoing'; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($a['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="editAssignment(<?php echo $a['assignment_id']; ?>)" class="p-2 hover:bg-gray-100 rounded-lg transition-colors" title="Edit">
                                        <i data-lucide="pencil" class="w-4 h-4 text-gray-600"></i>
                                    </button>
                                    <button onclick="deleteAssignment(<?php echo $a['assignment_id']; ?>)" class="p-2 hover:bg-gray-100 rounded-lg transition-colors" title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4 text-red-600"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($assignments)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <i data-lucide="clipboard-list" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                                <p>No assignments found</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">Showing 0-0 of 0</p>
                    <div class="flex items-center gap-2">
                        <button class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50" disabled>Prev</button>
                        <button class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50" disabled>Next</button>
                    </div>
                </div>
            </div>

            </div> <!-- End Tab Content: Assignments -->

            <!-- Tab Content: Group Assignments -->
            <div id="content-group-assignments" class="tab-content hidden">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center">
                    <i data-lucide="users-2" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Group Assignments</h3>
                    <p class="text-gray-500 mb-6">Assign employee groups to projects for easier bulk management.</p>
                    <button onclick="openBulkModal()" class="inline-flex items-center gap-2 bg-[#e9922c] text-white px-6 py-2.5 rounded-lg hover:bg-[#d17f1f] transition-colors">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Create Group Assignment
                    </button>
                    
                    <div class="mt-8 text-left">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-600 uppercase">Group Name</th>
                                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-600 uppercase">Project</th>
                                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-600 uppercase">Members</th>
                                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-600 uppercase">Status</th>
                                    <th class="text-center px-6 py-3 text-xs font-semibold text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        <p>No group assignments created yet</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> <!-- End Tab Content: Group Assignments -->

        </div>
    </main>

    <!-- Assignment Modal -->
    <?php include __DIR__ . '/components/assignment_modal.php'; ?>

    <script>
        lucide.createIcons();

        // Tab switching functionality
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            // Show selected tab content
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            // Update tab button styles
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-[#e9922c]', 'text-[#e9922c]');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            document.getElementById('tab-' + tabName).classList.remove('border-transparent', 'text-gray-500');
            document.getElementById('tab-' + tabName).classList.add('border-[#e9922c]', 'text-[#e9922c]');
            
            lucide.createIcons();
        }

        function openBulkModal() {
            showToast('Bulk assignment feature coming soon', 'info');
        }

        // Store data for JS
        const projectsData = <?php echo json_encode($projects); ?>;
        const employeesData = <?php echo json_encode($employees); ?>;
        const phasesData = <?php echo json_encode($phases); ?>;

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', filterTable);
        document.getElementById('statusFilter').addEventListener('change', filterTable);

        function filterTable() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const status = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('.assignment-row');
            let count = 0;

            rows.forEach(row => {
                const name = row.dataset.name;
                const rowStatus = row.dataset.status;
                const matchesSearch = name.includes(search);
                const matchesStatus = !status || rowStatus === status;

                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                    count++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('showingCount').textContent = count;
        }

        function openModal() {
            document.getElementById('assignmentModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = 'Add New Assignment';
            document.getElementById('assignmentForm').reset();
            document.getElementById('assignment_id').value = '';
        }

        function closeModal() {
            document.getElementById('assignmentModal').classList.add('hidden');
        }

        function editAssignment(id) {
            fetch('api/assignments.php?action=get&id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('assignmentModal').classList.remove('hidden');
                        document.getElementById('modalTitle').textContent = 'Edit Assignment';
                        document.getElementById('assignment_id').value = data.assignment.assignment_id;
                        document.getElementById('employee_id').value = data.assignment.employee_id;
                        document.getElementById('project_id').value = data.assignment.project_id;
                        updatePhases(data.assignment.project_id, data.assignment.phase_id);
                        document.getElementById('role').value = data.assignment.role || '';
                        document.getElementById('start_date').value = data.assignment.start_date || '';
                        document.getElementById('end_date').value = data.assignment.end_date || '';
                        document.getElementById('assignment_status').value = data.assignment.status;
                    }
                });
        }

        function deleteAssignment(id) {
            if (confirm('Are you sure you want to delete this assignment?')) {
                fetch('api/assignments.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', id: id })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast('Assignment deleted successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message || 'Error deleting assignment', 'error');
                    }
                });
            }
        }

        function updatePhases(projectId, selectedPhaseId = null) {
            const phaseSelect = document.getElementById('phase_id');
            phaseSelect.innerHTML = '<option value="">-- Select Phase --</option>';
            
            phasesData.filter(p => p.project_id == projectId).forEach(phase => {
                const option = document.createElement('option');
                option.value = phase.phase_id;
                option.textContent = phase.phase_name;
                if (selectedPhaseId && phase.phase_id == selectedPhaseId) {
                    option.selected = true;
                }
                phaseSelect.appendChild(option);
            });
        }

        function saveAssignment() {
            const form = document.getElementById('assignmentForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            data.action = data.assignment_id ? 'update' : 'create';

            fetch('api/assignments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    showToast('Assignment saved successfully', 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message || 'Error saving assignment', 'error');
                }
            });
        }
    </script>
</body>
</html>
