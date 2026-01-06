<?php
// modules/project/projects.php

// 1. Configuration (Session & Constants)
require_once __DIR__ . '/../../config/config.php';

// 2. Authentication Check
if (!isset($_SESSION['user_id'])) { 
    header("Location: " . BASE_URL . "index.php"); 
    exit(); 
}

// 3. Database Connection
require_once __DIR__ . '/../../config/database.php';

$sql = "SELECT p.*, CONCAT(e.first_name, ' ', e.last_name) AS manager_name
    FROM icmis_projects p 
    LEFT JOIN workforce_employees e ON p.project_manager_id = e.employee_id 
    ORDER BY p.project_id DESC";
$result = $conn->query($sql);

$projects = [];
$totalProjects = 0;
$activeCount = 0;
$completedCount = 0;
$upcomingCount = 0;
$totalBudget = 0;
$now = new DateTime();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
        $totalProjects++;
        $totalBudget += floatval($row['total_budget'] ?? 0);
        
        $status = strtolower($row['status'] ?? '');
        if ($status === 'planning' || $status === 'in progress' || $status === 'active') $activeCount++;
        if ($status === 'completed') $completedCount++;
        if (!empty($row['start_date']) && new DateTime($row['start_date']) > $now) $upcomingCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management | ICMIS</title>
    
    <!-- Global Head Assets (Tailwind, Fonts, Favicon) -->
    <?php include __DIR__ . '/../../includes/head_assets.php'; ?>
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        @keyframes modal-slide-in {
            from { opacity: 0; transform: translateY(-20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .animate-modal-slide-in { animation: modal-slide-in 0.3s ease-out forwards; }
    </style>
</head>
<body class="bg-gray-50">
    <?php 
        // Layout Components
        include __DIR__ . '/../../includes/sidebar.php';
        include __DIR__ . '/../../includes/toast.php';
    ?>
    
    <?php
        // Header Variables & Include
        $pageTitle = "Project Management";
        $pageSection = "Project Management";
        $pageSubTitle = '<span class="text-sm text-gray-500">Manage all construction projects</span>';
        include __DIR__ . '/../../includes/header.php'; 
    ?>

    <main class="ml-56 mt-16 p-6">
        <div class="max-w-7xl mx-auto">
            
            <!-- Tabs -->
            <div class="flex items-center gap-1 mb-6 border-b border-gray-200">
                <a href="projects.php" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-[#e9922c] text-[#e9922c] transition-colors">
                    Projects
                </a>
                <a href="phases.php" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                    Phases
                </a>
                <a href="tasks.php" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                    Tasks
                </a>
                <a href="gantt.php" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                    Gantt Chart
                </a>
            </div>

        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl text-gray-900 font-bold">Project Management</h1>
                <p class="text-sm text-gray-500 mt-1">Create, manage, and track all construction projects</p>
            </div>
        </div>

        <!-- Search, Filter, and Add Button -->
        <div class="flex items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-4 flex-1">
                <!-- Search -->
                <div class="relative flex-1 max-w-md">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" id="searchInput" placeholder="Search project name or code" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] text-sm">
                </div>
                
                <!-- Status Filter -->
                <select id="statusFilter" class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c]">
                    <option value="">All Status</option>
                    <option value="Planning">Planning</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Active">Active</option>
                    <option value="On Hold">On Hold</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>
            
            <button id="addProjectBtn" class="flex items-center gap-2 bg-[#e9922c] text-white px-6 py-2.5 rounded-lg hover:bg-[#d17f1f] transition-colors duration-200 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="font-medium">Add Project</span>
            </button>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Projects</p>
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo $totalProjects; ?></h2>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="bg-green-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Active Projects</p>
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo $activeCount; ?></h2>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="bg-purple-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Completed</p>
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo $completedCount; ?></h2>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="bg-orange-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Budget</p>
                        <h2 class="text-2xl font-bold text-gray-900">₱<?php echo number_format($totalBudget, 0); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Control Bar -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <!-- Search -->
                <div class="relative flex-1 max-w-md">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" id="projectSearch" placeholder="Search by project name or manager..." 
                        class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-colors text-sm">
                </div>
                
                <!-- Filters -->
                <div class="flex items-center gap-3">
                    <select id="projectStatusFilter" class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c]">
                        <option value="">All Status</option>
                        <option value="Planning">Planning</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Active">Active</option>
                        <option value="On Hold">On Hold</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                    
                    <select id="projectBudgetFilter" class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c]">
                        <option value="">All Budgets</option>
                        <option value="low">&lt; ₱1M</option>
                        <option value="mid">₱1M – ₱5M</option>
                        <option value="high">&gt; ₱5M</option>
                    </select>
                </div>
            </div>
        </div>

        <?php if (empty($projects)): ?>
        <!-- Empty State -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12">
            <div class="max-w-md mx-auto text-center">
                <div class="flex justify-center mb-6">
                    <div class="bg-orange-50 rounded-full p-6">
                        <svg class="w-16 h-16 text-[#e9922c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>

                <h2 class="text-xl text-gray-900 font-bold mb-3">No Projects Yet</h2>
                <p class="text-gray-500 mb-8 leading-relaxed">
                    Get started by creating your first project. You can manage construction projects, 
                    assign managers, and track budgets all in one place.
                </p>

                <button onclick="document.getElementById('addProjectBtn').click()" class="inline-flex items-center gap-2 bg-[#e9922c] text-white px-6 py-3 rounded-lg hover:bg-[#d17f1f] transition-all duration-200 shadow-md hover:shadow-lg font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Your First Project
                </button>
            </div>
        </div>
        <?php else: ?>
        <!-- Projects Table -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full" id="generalTable">
                    <thead>
                        <tr class="bg-gradient-to-r from-slate-800 to-slate-700">
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Project</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Manager</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Location</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Timeline</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Budget</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-white uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" id="projectsTableBody">
                        <?php foreach ($projects as $project): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-150 project-row" 
                            data-status="<?php echo strtolower($project['status'] ?? ''); ?>"
                            data-budget="<?php echo floatval($project['total_budget'] ?? 0); ?>"
                            data-name="<?php echo strtolower($project['project_name'] ?? ''); ?>"
                            data-manager="<?php echo strtolower($project['manager_name'] ?? ''); ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="bg-slate-100 rounded-lg p-2">
                                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($project['project_name']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($project['project_code'] ?? ''); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($project['manager_name'] ?? '-'); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-600"><?php echo htmlspecialchars($project['location'] ?? '-'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo !empty($project['start_date']) ? date('M d, Y', strtotime($project['start_date'])) : '-'; ?></div>
                                <?php if (!empty($project['end_date'])): ?>
                                <div class="text-xs text-gray-500">to <?php echo date('M d, Y', strtotime($project['end_date'])); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                    $statusClass = match(strtolower($project['status'] ?? '')) {
                                        'planning' => 'bg-blue-100 text-blue-700',
                                        'in progress', 'active' => 'bg-green-100 text-green-700',
                                        'on hold' => 'bg-yellow-100 text-yellow-700',
                                        'completed' => 'bg-purple-100 text-purple-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                        default => 'bg-gray-100 text-gray-700'
                                    };
                                ?>
                                <span class="px-3 py-1 text-xs font-bold rounded-full <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($project['status'] ?? 'Unknown'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-900">₱<?php echo number_format($project['total_budget'] ?? 0, 2); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button class="edit-btn text-gray-500 p-2 rounded-lg hover:text-green-600 hover:bg-green-50 transition-colors duration-200" 
                                            data-id="<?php echo $project['project_id']; ?>" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    
                                    <button class="delete-btn text-gray-500 p-2 rounded-lg hover:text-red-600 hover:bg-red-50 transition-colors duration-200" 
                                            data-id="<?php echo $project['project_id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($project['project_name'], ENT_QUOTES); ?>" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Project Modal (Add/Edit) -->
    <?php include __DIR__ . '/components/project_modal.php'; ?>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all animate-modal-slide-in">
            <div class="bg-gradient-to-r from-red-600 to-red-700 p-6 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="bg-white rounded-full p-3 shadow-lg">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-white">Delete Project</h3>
                            <p class="text-red-100 text-sm mt-1">Permanent action</p>
                        </div>
                    </div>
                    <button onclick="closeDeleteModal()" class="text-white hover:bg-white/10 p-2 rounded-lg transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-8">
                <p class="text-gray-700 text-lg font-medium mb-2">Are you sure you want to delete this project?</p>
                <p class="text-gray-500 text-sm mb-6">This will also delete all phases and tasks associated with this project.</p>
                
                <div class="bg-red-50 border-2 border-red-200 rounded-xl p-5">
                    <div class="flex items-start gap-4">
                        <div class="bg-red-100 rounded-full p-2 shrink-0">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-red-900 mb-1.5 uppercase tracking-wide">Project Name</p>
                            <p id="deleteProjectName" class="text-lg font-bold text-red-700 bg-white px-3 py-2 rounded-lg border border-red-200"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 p-6 bg-gray-50 border-t border-gray-200 rounded-b-2xl">
                <button onclick="closeDeleteModal()" class="px-6 py-3 text-gray-700 bg-white border-2 border-gray-300 rounded-xl hover:bg-gray-100 transition-all font-semibold">
                    Cancel
                </button>
                <button id="confirmDeleteBtn" onclick="confirmDelete()" class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-xl transition-all font-semibold flex items-center gap-2 shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete Project
                </button>
            </div>
        </div>
    </div>

    <script>
        const backendUrl = "api/projects.php";
        const employeesUrl = "api/employees.php";
        let projectToDelete = null;

        // ------------------ Search & Filter ------------------
        function filterProjects() {
            const searchTerm = document.getElementById('projectSearch').value.toLowerCase();
            const statusFilter = document.getElementById('projectStatusFilter').value.toLowerCase();
            const budgetFilter = document.getElementById('projectBudgetFilter').value;
            
            document.querySelectorAll('.project-row').forEach(row => {
                const name = row.dataset.name || '';
                const manager = row.dataset.manager || '';
                const status = row.dataset.status || '';
                const budget = parseFloat(row.dataset.budget) || 0;
                
                let show = true;
                
                // Search filter
                if (searchTerm && !name.includes(searchTerm) && !manager.includes(searchTerm)) {
                    show = false;
                }
                
                // Status filter
                if (statusFilter && status !== statusFilter) {
                    show = false;
                }
                
                // Budget filter
                if (budgetFilter) {
                    if (budgetFilter === 'low' && budget >= 1000000) show = false;
                    if (budgetFilter === 'mid' && (budget < 1000000 || budget > 5000000)) show = false;
                    if (budgetFilter === 'high' && budget <= 5000000) show = false;
                }
                
                row.style.display = show ? '' : 'none';
            });
        }

        document.getElementById('projectSearch')?.addEventListener('input', filterProjects);
        document.getElementById('projectStatusFilter')?.addEventListener('change', filterProjects);
        document.getElementById('projectBudgetFilter')?.addEventListener('change', filterProjects);

        // ------------------ Add Project ------------------
        document.getElementById('addProjectBtn')?.addEventListener('click', function() {
            document.getElementById('projectForm').reset();
            document.getElementById('projectModalTitle').textContent = 'Add Project';
            document.getElementById('projectModalBtnText').textContent = 'Add Project';
            document.getElementById('project_id').value = '';
                // Clear budget display and hidden raw value
                if (document.getElementById('total_budget')) document.getElementById('total_budget').value = '';
                if (document.getElementById('total_budget_display')) document.getElementById('total_budget_display').value = '';

            // Get next project code
            fetch(backendUrl + '?get_next_id=1')
                .then(res => res.json())
                .then(data => {
                    if (data.success) document.getElementById('project_code').value = data.project_code;
                });

            // Fetch active employees for manager select
            fetch(employeesUrl)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('managerSelect');
                        select.innerHTML = '<option value="">Select Project Manager</option>';
                        data.employees.forEach(emp => {
                            const fullName = emp.first_name + ' ' + emp.last_name;
                            select.innerHTML += `<option value="${emp.employee_id}">${fullName} (${emp.employee_code})</option>`;
                        });
                    }
                });

            document.getElementById('projectModal').style.display = 'flex';
        });

        // ------------------ Edit Project ------------------
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const projectId = this.dataset.id;

                fetch(backendUrl + '?fetch_id=' + projectId)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const project = data.project;
                            document.getElementById('project_id').value = project.project_id;
                            document.getElementById('project_code').value = project.project_code;
                            document.getElementById('project_name').value = project.project_name;
                            document.getElementById('description').value = project.description || '';
                            document.getElementById('location').value = project.location || '';
                            document.getElementById('start_date').value = project.start_date || '';
                            document.getElementById('end_date').value = project.end_date || '';
                            document.getElementById('status').value = project.status || 'Planning';
                            // Raw hidden value
                            document.getElementById('total_budget').value = project.total_budget || '';
                            // Formatted display
                            const tbDisplay = document.getElementById('total_budget_display');
                            if (tbDisplay) {
                                const raw = (project.total_budget || '').toString();
                                tbDisplay.value = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
                            }

                            // Fetch employees and populate manager select
                            fetch(employeesUrl)
                                .then(res => res.json())
                                .then(empData => {
                                    if (empData.success) {
                                        const select = document.getElementById('managerSelect');
                                        select.innerHTML = '<option value="">Select Project Manager</option>';
                                        empData.employees.forEach(emp => {
                                            const fullName = emp.first_name + ' ' + emp.last_name;
                                            const selected = project.project_manager_id == emp.employee_id ? 'selected' : '';
                                            select.innerHTML += `<option value="${emp.employee_id}" ${selected}>${fullName} (${emp.employee_code})</option>`;
                                        });
                                        document.getElementById('projectModalTitle').textContent = 'Edit Project';
                                        document.getElementById('projectModalBtnText').textContent = 'Save Changes';
                                        document.getElementById('projectModal').style.display = 'flex';
                                    }
                                });
                        } else {
                            showToast(data.message || 'Error fetching project', 'error');
                        }
                    });
            });
        });

        // ------------------ Delete Project ------------------
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                projectToDelete = this.dataset.id;
                document.getElementById('deleteProjectName').textContent = this.dataset.name;
                document.getElementById('deleteModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            });
        });

        function closeDeleteModal() {
            projectToDelete = null;
            document.getElementById('deleteModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function confirmDelete() {
            if (!projectToDelete) return;

            const btn = document.getElementById('confirmDeleteBtn');
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Deleting...`;

            const formData = new FormData();
            formData.append('delete_id', projectToDelete);

            fetch(backendUrl, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Project deleted successfully', 'success', true);
                        closeDeleteModal();
                        setTimeout(() => window.location.reload(), 300);
                    } else {
                        showToast(data.message || 'Error deleting project', 'error');
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                    }
                })
                .catch(err => {
                    showToast('Error deleting project: ' + err.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                });
        }

        // Close modal on outside click
        document.getElementById('deleteModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('deleteModal').classList.contains('hidden')) {
                closeDeleteModal();
            }
        });

        // ------------------ Close Project Modal ------------------
        function closeProjectModal() {
            document.getElementById('projectForm').reset();
            // also clear formatted display
            if (document.getElementById('total_budget_display')) document.getElementById('total_budget_display').value = '';
            document.getElementById('projectModal').style.display = 'none';
        }
        document.getElementById('closeProjectModal')?.addEventListener('click', closeProjectModal);
        document.getElementById('cancelProjectModal')?.addEventListener('click', closeProjectModal);

        // ------------------ Submit Add/Edit ------------------
        document.getElementById('projectForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);

            fetch(backendUrl, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Project saved successfully', 'success', true);
                        closeProjectModal();
                        setTimeout(() => window.location.reload(), 300);
                    } else {
                        showToast(data.message || 'Error saving project', 'error');
                    }
                })
                .catch(err => {
                    showToast('Error: ' + err.message, 'error');
                });
        });

        // ------------------ Search and Filter ------------------
        function filterProjects() {
            const searchTerm = document.getElementById('projectSearch')?.value.toLowerCase() || '';
            const statusFilter = document.getElementById('projectStatusFilter')?.value.toLowerCase() || '';
            const budgetFilter = document.getElementById('projectBudgetFilter')?.value || '';
            
            const rows = document.querySelectorAll('.project-row');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const name = row.dataset.name || '';
                const manager = row.dataset.manager || '';
                const status = row.dataset.status || '';
                const budget = parseFloat(row.dataset.budget) || 0;
                
                // Search match (project name or manager)
                const searchMatch = !searchTerm || name.includes(searchTerm) || manager.includes(searchTerm);
                
                // Status match
                const statusMatch = !statusFilter || status === statusFilter;
                
                // Budget match
                let budgetMatch = true;
                if (budgetFilter === 'low') {
                    budgetMatch = budget < 1000000;
                } else if (budgetFilter === 'mid') {
                    budgetMatch = budget >= 1000000 && budget <= 5000000;
                } else if (budgetFilter === 'high') {
                    budgetMatch = budget > 5000000;
                }
                
                // Show/hide row
                if (searchMatch && statusMatch && budgetMatch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show no results message if needed
            const tableBody = document.getElementById('projectsTableBody');
            let noResultsRow = document.getElementById('noResultsRow');
            
            if (visibleCount === 0 && rows.length > 0) {
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.id = 'noResultsRow';
                    noResultsRow.innerHTML = `
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <p class="text-sm font-medium">No projects match your filters</p>
                                <p class="text-xs mt-1">Try adjusting your search or filter criteria</p>
                            </div>
                        </td>
                    `;
                    tableBody?.appendChild(noResultsRow);
                }
                noResultsRow.style.display = '';
            } else if (noResultsRow) {
                noResultsRow.style.display = 'none';
            }
        }
        
        // Attach event listeners
        document.getElementById('projectSearch')?.addEventListener('input', filterProjects);
        document.getElementById('projectStatusFilter')?.addEventListener('change', filterProjects);
        document.getElementById('projectBudgetFilter')?.addEventListener('change', filterProjects);
    </script>
</body>
</html>
