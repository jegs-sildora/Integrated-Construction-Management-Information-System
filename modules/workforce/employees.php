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
$pageTitle = "Employee Management";
$pageSubTitle = $breadcrumbHTML;

// Get employee stats
$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
    'new_this_month' => 0
];

$result = $conn->query("SELECT COUNT(*) as total FROM workforce_employees");
if ($result) $stats['total'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM workforce_employees WHERE status = 'Active'");
if ($result) $stats['active'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM workforce_employees WHERE status = 'Inactive'");
if ($result) $stats['inactive'] = $result->fetch_assoc()['total'];

$thisMonth = date('Y-m-01');
$result = $conn->query("SELECT COUNT(*) as total FROM workforce_employees WHERE hire_date >= '$thisMonth'");
if ($result) $stats['new_this_month'] = $result->fetch_assoc()['total'];

// Fetch employees with job titles
// DB Schema: workforce_employees (employee_id, employee_code, user_id, job_title_id, first_name, last_name, email, phone, status, hire_date)
// DB Schema: workforce_job_titles (job_title_id, title_name, department, description, default_daily_rate, is_active)
$employees = [];
$sql = "SELECT e.*, jt.title_name as job_title, jt.department, jt.default_daily_rate
        FROM workforce_employees e 
        LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
        ORDER BY e.employee_id DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}

$userName = $_SESSION['user_name'] ?? "Admin";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management | ICMIS</title>
    
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
                <button onclick="switchTab('employees')" id="tab-employees" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-[#e9922c] text-[#e9922c] transition-colors">
                    Employees
                </button>
                <button onclick="switchTab('groups')" id="tab-groups" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                    Employee Groups
                </button>
            </div>

            <!-- Page Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl text-gray-900 font-bold">Employee Management</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage, View, and Edit Employee Profiles</p>
                </div>
                <button onclick="openModal()" class="flex items-center gap-2 bg-[#e9922c] text-white px-6 py-2.5 rounded-lg hover:bg-[#d17f1f] transition-colors duration-200 shadow-sm font-bold">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    Add Employee
                </button>
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
                            <p class="text-xs text-gray-500">Active</p>
                            <h3 class="text-xl font-bold text-gray-900"><?php echo $stats['active']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="user-x" class="w-5 h-5 text-red-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Inactive</p>
                            <h3 class="text-xl font-bold text-gray-900"><?php echo $stats['inactive']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="user-plus" class="w-5 h-5 text-orange-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">New This Month</p>
                            <h3 class="text-xl font-bold text-gray-900"><?php echo $stats['new_this_month']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Employees -->
            <div id="content-employees" class="tab-content">

            <!-- Search and Filter -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="text" id="searchInput" placeholder="Search by name, role, or ID" class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none w-72">
                        </div>
                        <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Terminated">Terminated</option>
                        </select>
                    </div>
                    <p class="text-sm text-gray-500">Showing <span id="showingCount"><?php echo count($employees); ?></span> employees</p>
                </div>
            </div>

            <!-- Employees Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Employee</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Position</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Department</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Daily Rate</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="employeesTableBody" class="divide-y divide-gray-100">
                        <?php foreach ($employees as $emp): 
                            $initials = strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1));
                            $statusClass = match(strtolower($emp['status'])) {
                                'active' => 'bg-green-100 text-green-700',
                                'inactive' => 'bg-red-100 text-red-700',
                                'terminated' => 'bg-gray-100 text-gray-700',
                                default => 'bg-gray-100 text-gray-700'
                            };
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors employee-row" 
                            data-name="<?php echo strtolower($emp['first_name'] . ' ' . $emp['last_name']); ?>"
                            data-status="<?php echo $emp['status']; ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="employee-avatar"><?php echo $initials; ?></div>
                                    <div>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></p>
                                        <p class="text-xs text-gray-500">#<?php echo htmlspecialchars($emp['employee_code']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($emp['job_title'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($emp['department'] ?? 'General'); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                    ₱<?php echo number_format($emp['default_daily_rate'] ?? 0, 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($emp['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="viewEmployee(<?php echo $emp['employee_id']; ?>)" class="text-gray-500 p-2 rounded-lg hover:text-blue-600 transition-colors duration-200" title="View Details">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button onclick="editEmployee(<?php echo $emp['employee_id']; ?>)"  class="text-gray-500 p-2 rounded-lg hover:text-green-600 transition-colors duration-200" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button onclick="deleteEmployee(<?php echo $emp['employee_id']; ?>)" class="text-gray-500 p-2 rounded-lg hover:text-red-600 transition-colors duration-200" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i data-lucide="users" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                                <p>No employees found</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            </div> <!-- End Tab Content: Employees -->

            <!-- Tab Content: Employee Groups -->
            <div id="content-groups" class="tab-content hidden">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center">
                    <i data-lucide="users-2" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Employee Groups</h3>
                    <p class="text-gray-500 mb-6">Organize employees into groups for easier management and bulk assignments.</p>
                    <button onclick="openGroupModal()" class="inline-flex items-center gap-2 bg-[#e9922c] text-white px-6 py-2.5 rounded-lg hover:bg-[#d17f1f] transition-colors">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Create Group
                    </button>
                    
                    <div class="mt-8 text-left">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-600 uppercase">Group Name</th>
                                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-600 uppercase">Members</th>
                                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-600 uppercase">Description</th>
                                    <th class="text-center px-6 py-3 text-xs font-semibold text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                        <p>No groups created yet</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> <!-- End Tab Content: Employee Groups -->

        </div>
    </main>

    <!-- Add/Edit Employee Modal -->
    <?php include __DIR__ . '/components/employee_modal.php'; ?>

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

        function openGroupModal() {
            showToast('Group management feature coming soon', 'info');
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', filterTable);
        document.getElementById('statusFilter').addEventListener('change', filterTable);

        function filterTable() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const status = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('.employee-row');
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

        function showEmployeeModal() {
            const modal = document.getElementById('employeeModal');
            const content = modal.querySelector('.modal-content');
            // ensure visible
            modal.classList.remove('hidden');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            // play open animation
            content.classList.remove('modal-close');
            void content.offsetWidth; // force reflow
            content.classList.add('modal-open');
        }

        function openModal() {
            document.getElementById('modalTitle').textContent = 'Add New Employee';
            document.getElementById('employeeForm').reset();
            showEmployeeModal();
        }

        function closeModal() {
            const modal = document.getElementById('employeeModal');
            const content = modal.querySelector('.modal-content');
            // play close animation then hide
            content.classList.remove('modal-open');
            content.classList.add('modal-close');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.style.display = '';
                document.body.style.overflow = '';
            }, 240);
        }

        function viewEmployee(id) {
            window.location.href = 'employee_profile.php?id=' + id;
        }

        function editEmployee(id) {
            // Fetch employee data and open modal
            fetch('api/employees.php?action=get&id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('modalTitle').textContent = 'Edit Employee';
                        // Populate form fields
                        document.getElementById('employee_id').value = data.employee.employee_id;
                        document.getElementById('first_name').value = data.employee.first_name;
                        document.getElementById('last_name').value = data.employee.last_name;
                        document.getElementById('email').value = data.employee.email || '';
                        document.getElementById('phone').value = data.employee.phone || '';
                        document.getElementById('status').value = data.employee.status;
                        // Show modal with animation
                        showEmployeeModal();
                    }
                });
        }

        function deleteEmployee(id) {
            if (confirm('Are you sure you want to delete this employee?')) {
                fetch('api/employees.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', id: id })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast('Employee deleted successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message || 'Error deleting employee', 'error');
                    }
                });
            }
        }

        function saveEmployee() {
            const form = document.getElementById('employeeForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            data.action = data.employee_id ? 'update' : 'create';

            fetch('api/employees.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    showToast('Employee saved successfully', 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message || 'Error saving employee', 'error');
                }
            });
        }
    </script>
</body>
</html>
