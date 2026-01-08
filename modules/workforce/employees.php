<?php
// ============================================================
// ALL PHP LOGIC MUST BE BEFORE ANY HTML OUTPUT
// ============================================================

include __DIR__ . '/project_context.php';
$conn = getWorkforceConnection();

$selected_project_id = getProjectContext($conn);

// make job titles available for server-side rendering in included components
include __DIR__ . '/api/job_titles_include.php';

// --- HELPER FUNCTION FOR STATS ---
function getEmployeeStats($conn) {
    $stats = [ 'total' => 0, 'active' => 0, 'inactive' => 0, 'new_this_month' => 0 ];
    
    $result = $conn->query("SELECT COUNT(*) as total FROM workforce_employees");
    if ($result) $stats['total'] = $result->fetch_assoc()['total'];

    $result = $conn->query("SELECT COUNT(*) as total FROM workforce_employees WHERE status = 'Active'");
    if ($result) $stats['active'] = $result->fetch_assoc()['total'];

    $result = $conn->query("SELECT COUNT(*) as total FROM workforce_employees WHERE status = 'Inactive'");
    if ($result) $stats['inactive'] = $result->fetch_assoc()['total'];

    $thisMonth = date('Y-m-01');
    $result = $conn->query("SELECT COUNT(*) as total FROM workforce_employees WHERE hire_date >= '$thisMonth'");
    if ($result) $stats['new_this_month'] = $result->fetch_assoc()['total'];
    
    return $stats;
}

// --- PAGINATION & DATA FETCH LOGIC ---
$limit = 15; // Items per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Fetch Employees Function
function fetchEmployees($conn, $limit, $offset) {
    $employees = [];
    $sql = "SELECT e.*, jt.title_name as job_title, jt.department, jt.default_daily_rate
            FROM workforce_employees e 
            LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
            ORDER BY e.employee_id DESC
            LIMIT $limit OFFSET $offset";

    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
    }
    return $employees;
}

// --- AJAX REQUEST HANDLER ---
// If the JS requests 'fetch_updates', we return JSON and exit.
if (isset($_GET['fetch_updates'])) {
    // 1. Get Stats
    $stats = getEmployeeStats($conn);
    
    // 2. Get Pagination Info
    $total_pages_sql = "SELECT COUNT(*) as total FROM workforce_employees"; 
    $total_pages_result = $conn->query($total_pages_sql);
    $total_rows = $total_pages_result->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $limit);
    
    // 3. Get Employees
    $employees = fetchEmployees($conn, $limit, $offset);
    
    // 4. Render Table HTML
    ob_start();
    if (count($employees) > 0):
        foreach ($employees as $emp): 
            $initials = strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1));
            $statusClass = match(strtolower($emp['status'])) {
                'active' => 'bg-green-100 text-green-700',
                'inactive' => 'bg-red-100 text-red-700',
                'terminated' => 'bg-gray-100 text-gray-700',
                default => 'bg-gray-100 text-gray-700'
            };
    ?>
    <tr class="hover:bg-gray-50 transition-colors employee-row text-center" 
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
        <td class="px-6 py-4 text-gray-600 font-bold"><?php echo htmlspecialchars($emp['job_title'] ?? 'N/A'); ?></td>
        <td class="px-6 py-4 text-gray-600 font-bold"><?php echo htmlspecialchars($emp['department'] ?? 'General'); ?></td>
        <td class="px-6 py-4">
            <span class="px-2 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
                ₱<?php echo number_format($emp['default_daily_rate'] ?? 0, 2); ?>
            </span>
        </td>
        <td class="px-6 py-4">
            <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $statusClass; ?>">
                <?php echo htmlspecialchars($emp['status']); ?>
            </span>
        </td>
        <td class="px-6 py-4">
            <div class="flex items-center justify-center gap-2">
                <button onclick="viewEmployee(<?php echo $emp['employee_id']; ?>)" class="text-gray-500 p-2 rounded-lg hover:text-blue-600 transition-colors duration-200" title="View Details">
                    <i data-lucide="eye" class="w-5 h-5"></i>
                </button>
                <button onclick="openEditModal(<?php echo $emp['employee_id']; ?>)"  class="text-gray-500 p-2 rounded-lg hover:text-green-600 transition-colors duration-200" title="Edit">
                    <i data-lucide="edit" class="w-5 h-5"></i>
                </button>
                <button onclick="openDeleteModal(<?php echo $emp['employee_id']; ?>)" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Delete">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                </button>
            </div>
        </td>
    </tr>
    <?php endforeach; 
    else: ?>
        <tr>
            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                No employees found.
            </td>
        </tr>
    <?php endif; 
    $tableHtml = ob_get_clean();

    // 5. Render Pagination HTML
    ob_start();
    if ($total_pages > 1): ?>
    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
        <div class="text-sm text-gray-500">
            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $limit, $total_rows); ?></span> of <span class="font-medium"><?php echo $total_rows; ?></span> results
        </div>
        <div class="flex gap-2">
            <?php if ($page > 1): ?>
                <a href="?project_id=<?php echo $selected_project_id; ?>&page=<?php echo $page - 1; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</a>
            <?php else: ?>
                <span class="px-3 py-1 bg-gray-100 border border-gray-300 rounded-md text-sm font-medium text-gray-400 cursor-not-allowed">Previous</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                    <?php if ($i == $page): ?>
                        <span class="px-3 py-1 bg-[#e9922c] border border-[#e9922c] rounded-md text-sm font-medium text-white"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?project_id=<?php echo $selected_project_id; ?>&page=<?php echo $i; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                    <span class="px-2 py-1 text-gray-500">...</span>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?project_id=<?php echo $selected_project_id; ?>&page=<?php echo $page + 1; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Next</a>
            <?php else: ?>
                <span class="px-3 py-1 bg-gray-100 border border-gray-300 rounded-md text-sm font-medium text-gray-400 cursor-not-allowed">Next</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; 
    $paginationHtml = ob_get_clean();

    // Return JSON
    header('Content-Type: application/json');
    echo json_encode([
        'stats' => $stats,
        'tableHtml' => $tableHtml,
        'paginationHtml' => $paginationHtml
    ]);
    exit;
}
// --- END AJAX HANDLER ---

// Standard Page Load Logic
$projects = [];
$sql_projects = "SELECT project_id, project_code, project_name FROM icmis_projects ORDER BY project_id DESC";
$result_projects = $conn->query($sql_projects);
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

// Initial Stats Load
$stats = getEmployeeStats($conn);

// Initial Employee Load
$total_pages_sql = "SELECT COUNT(*) as total FROM workforce_employees"; 
$total_pages_result = $conn->query($total_pages_sql);
$total_rows = $total_pages_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
$employees = fetchEmployees($conn, $limit, $offset);

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
            
            <div class="flex items-center gap-1 mb-6 border-b border-gray-200">
                <button onclick="window.location.href='employees.php'" id="tab-employees" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-[#e9922c] text-[#e9922c] transition-colors">
                    All Employees
                </button>
                <button onclick="window.location.href='employee_groups.php'" id="tab-groups" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                    Deployment Groups
                </button>
            </div>

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl text-gray-900 font-bold">Employee Management</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage, View, and Edit Employee Profiles</p>
                </div>
                <button onclick="openCreateModal()" class="flex items-center gap-2 bg-[#e9922c] text-white px-6 py-2.5 rounded-lg hover:bg-[#d17f1f] transition-colors duration-200 shadow-sm font-bold">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    Add Employee
                </button>
            </div>

            <div class="grid grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="users" class="w-5 h-5 text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Total Employees</p>
                            <h3 id="stat-total" class="text-xl font-black text-gray-900"><?php echo $stats['total']; ?></h3>
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
                            <h3 id="stat-active" class="text-xl font-black text-gray-900"><?php echo $stats['active']; ?></h3>
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
                            <h3 id="stat-inactive" class="text-xl font-black text-gray-900"><?php echo $stats['inactive']; ?></h3>
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
                            <h3 id="stat-new" class="text-xl font-black text-gray-900"><?php echo $stats['new_this_month']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div id="content-employees" class="tab-content">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                                <input type="text" id="searchInput" placeholder="Search on this page..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none w-263">
                            </div>
                            <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none">
                                <option value="">All Status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Terminated">Terminated</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Employee</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Position</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Department</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Daily Rate</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="employeesTableBody" class="divide-y divide-gray-100">
                            <?php if (count($employees) > 0): ?>
                                <?php foreach ($employees as $emp): 
                                    $initials = strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1));
                                    $statusClass = match(strtolower($emp['status'])) {
                                        'active' => 'bg-green-100 text-green-700',
                                        'inactive' => 'bg-red-100 text-red-700',
                                        'terminated' => 'bg-gray-100 text-gray-700',
                                        default => 'bg-gray-100 text-gray-700'
                                    };
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors employee-row text-center" 
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
                                    <td class="px-6 py-4 text-gray-600 font-bold"><?php echo htmlspecialchars($emp['job_title'] ?? 'N/A'); ?></td>
                                    <td class="px-6 py-4 text-gray-600 font-bold"><?php echo htmlspecialchars($emp['department'] ?? 'General'); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
                                            ₱<?php echo number_format($emp['default_daily_rate'] ?? 0, 2); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($emp['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <button onclick="viewEmployee(<?php echo $emp['employee_id']; ?>)" class="text-gray-500 p-2 rounded-lg hover:text-blue-600 transition-colors duration-200" title="View Details">
                                                <i data-lucide="eye" class="w-5 h-5"></i>
                                            </button>
                                            <button onclick="openEditModal(<?php echo $emp['employee_id']; ?>)"  class="text-gray-500 p-2 rounded-lg hover:text-green-600 transition-colors duration-200" title="Edit">
                                                <i data-lucide="edit" class="w-5 h-5"></i>
                                            </button>
                                            <button onclick="openDeleteModal(<?php echo $emp['employee_id']; ?>)" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Delete">
                                                <i data-lucide="trash-2" class="w-5 h-5"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                        No employees found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <div id="paginationContainer">
                    <?php if ($total_pages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $limit, $total_rows); ?></span> of <span class="font-medium"><?php echo $total_rows; ?></span> results
                        </div>
                        <div class="flex gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?project_id=<?php echo $selected_project_id; ?>&page=<?php echo $page - 1; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</a>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-gray-100 border border-gray-300 rounded-md text-sm font-medium text-gray-400 cursor-not-allowed">Previous</span>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <?php if ($i == $page): ?>
                                        <span class="px-3 py-1 bg-[#e9922c] border border-[#e9922c] rounded-md text-sm font-medium text-white"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?project_id=<?php echo $selected_project_id; ?>&page=<?php echo $i; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <span class="px-2 py-1 text-gray-500">...</span>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?project_id=<?php echo $selected_project_id; ?>&page=<?php echo $page + 1; ?>" class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Next</a>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-gray-100 border border-gray-300 rounded-md text-sm font-medium text-gray-400 cursor-not-allowed">Next</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div> 

            <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeDeleteModal()"></div>
                    
                    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all modal-content">
                        
                        <div class="bg-gradient-to-r from-red-600 to-red-700 p-6 rounded-t-2xl">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="bg-white rounded-full p-3 shadow-lg">
                                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-2xl font-bold text-white">Delete Employee</h3>
                                        <p class="text-red-100 text-sm mt-1">Permanent action</p>
                                    </div>
                                </div>
                                <button onclick="closeDeleteModal()" class="text-white hover:bg-white/10 hover:bg-opacity-60 p-2 rounded-lg transition-all">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-8">
                            <div class="mb-6">
                                <p class="text-gray-700 text-lg font-medium mb-2">
                                    Are you sure you want to delete this employee?
                                </p>
                                <p class="text-gray-500 text-sm">
                                    This action is permanent and cannot be undone. All associated data will be removed.
                                </p>
                            </div>
                            
                            <div class="bg-red-50 border-2 border-red-200 rounded-xl p-5 shadow-sm">
                                <div class="flex items-start gap-4">
                                    <div class="bg-red-100 rounded-full p-2 shrink-0">
                                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-bold text-red-900 mb-1.5 uppercase tracking-wide">Employee Name</p>
                                        <p id="deleteEmployeeName" class="text-lg font-bold text-red-700 font-mono bg-white px-3 py-2 rounded-lg border border-red-200"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 p-6 bg-gray-50 border-t border-gray-200 rounded-b-2xl">
                            <button onclick="closeDeleteModal()" class="px-6 py-3 text-gray-700 bg-white border-2 border-gray-300 rounded-xl hover:bg-gray-100 hover:border-gray-400 transition-all font-semibold shadow-sm">
                                Cancel
                            </button>
                            <button id="confirmDeleteBtn" onclick="confirmDelete()" class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-xl transition-all font-semibold flex items-center gap-2 shadow-lg hover:shadow-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Delete Employee
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="content-groups" class="tab-content hidden">
                 <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Employee Groups</h3>
                    <p class="text-gray-500">Coming Soon</p>
                 </div>
            </div>

        </div>
    </main>

    <?php include __DIR__ . '/components/employee_modal.php'; ?>

    <script src="js/employees.js"></script>
</body>
</html>