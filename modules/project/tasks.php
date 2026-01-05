<?php
// modules/project/tasks.php

// 1. Configuration (Session & Constants)
require_once __DIR__ . '/../../config/config.php';

// 2. Authentication Check
if (!isset($_SESSION['user_id'])) { 
    header("Location: " . BASE_URL . "index.php"); 
    exit(); 
}

// 3. Database Connection
require_once __DIR__ . '/../../config/database.php';

// Fetch all tasks across all projects
$sql = "SELECT t.*, 
               p.project_name, p.project_code,
               ph.phase_name,
               CONCAT(e.first_name, ' ', e.last_name) as assignee_name
        FROM icmis_tasks t 
        LEFT JOIN icmis_projects p ON t.project_id = p.project_id
        LEFT JOIN icmis_project_phases ph ON t.phase_id = ph.phase_id
        LEFT JOIN workforce_employees e ON t.assigned_to_employee_id = e.employee_id
        ORDER BY t.due_date ASC, t.task_id DESC";
$result = $conn->query($sql);

$tasks = [];
$totalTasks = 0;
$inProgressCount = 0;
$totalEstHours = 0;
$overdueCount = 0;
$now = new DateTime();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
        $totalTasks++;
        
        $status = strtolower($row['status'] ?? '');
        if ($status === 'in progress') $inProgressCount++;
        
        // Check if overdue (due_date passed and not completed)
        if (!empty($row['due_date']) && $status !== 'completed') {
            $dueDate = new DateTime($row['due_date']);
            if ($dueDate < $now) {
                $overdueCount++;
            }
        }
    }
}

// Fetch all projects for dropdown filter
$projectsResult = $conn->query("SELECT project_id, project_name, project_code FROM icmis_projects ORDER BY project_name ASC");
$allProjects = [];
while ($row = $projectsResult->fetch_assoc()) {
    $allProjects[] = $row;
}

// Fetch all phases for dropdown
$phasesResult = $conn->query("SELECT ph.phase_id, ph.phase_name, ph.project_id, p.project_name 
                              FROM icmis_project_phases ph 
                              LEFT JOIN icmis_projects p ON ph.project_id = p.project_id 
                              ORDER BY p.project_name, ph.phase_name ASC");
$allPhases = [];
while ($row = $phasesResult->fetch_assoc()) {
    $allPhases[] = $row;
}

// Fetch all employees for dropdown
$employeesResult = $conn->query("SELECT employee_id, employee_code, first_name, last_name FROM workforce_employees ORDER BY first_name, last_name ASC");
$allEmployees = [];
while ($row = $employeesResult->fetch_assoc()) {
    $allEmployees[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management | ICMIS</title>
    
    <!-- Global Head Assets -->
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
        include __DIR__ . '/../../includes/sidebar.php';
        include __DIR__ . '/../../includes/toast.php';
    ?>
    
    <?php
        $pageTitle = "Task Management";
        $pageSection = "Project Management";
        $pageSubTitle = '<span class="text-sm text-gray-500">Define and manage project tasks, assignments, and hours</span>';
        include __DIR__ . '/../../includes/header.php'; 
    ?>

    <main class="ml-56 mt-16 p-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl text-gray-900 font-bold">Task Management</h1>
                <p class="text-sm text-gray-500 mt-1">Define and manage project tasks, assignments, and hours.</p>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6">
            <div class="flex">
                <a href="projects.php" class="flex items-center gap-2 px-6 py-4 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Projects
                </a>
                <a href="phases.php" class="flex items-center gap-2 px-6 py-4 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    Phases
                </a>
                <a href="tasks.php" class="flex items-center gap-2 px-6 py-4 text-sm font-semibold border-b-2 border-[#e9922c] text-[#e9922c] bg-orange-50/50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Tasks
                </a>
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
                    <input type="text" id="searchInput" placeholder="Search tasks..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] text-sm">
                </div>
                
                <!-- Filter -->
                <select id="projectFilter" class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c]">
                    <option value="">Filter</option>
                    <?php foreach ($allProjects as $proj): ?>
                    <option value="<?php echo $proj['project_id']; ?>"><?php echo htmlspecialchars($proj['project_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button id="addTaskBtn" class="flex items-center gap-2 bg-[#e9922c] text-white px-6 py-2.5 rounded-lg hover:bg-[#d17f1f] transition-colors duration-200 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="font-medium">Add Task</span>
            </button>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Tasks</p>
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo $totalTasks; ?></h2>
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
                        <p class="text-sm text-gray-500">In Progress</p>
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo $inProgressCount; ?></h2>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="bg-purple-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Est. Hours</p>
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo $totalEstHours; ?></h2>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="bg-red-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Overdue</p>
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo $overdueCount; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tasks Table -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full" id="tasksTable">
                    <thead>
                        <tr class="bg-gradient-to-r from-slate-800 to-slate-700">
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Task Name</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Project / Phase</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Assigned To</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Due Date</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Priority</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Hours</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-white uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" id="tasksTableBody">
                        <?php if (empty($tasks)): ?>
                        <tr id="emptyRow">
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                    <p class="text-sm">No tasks found</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                        <?php
                            $isOverdue = false;
                            if (!empty($task['due_date']) && strtolower($task['status'] ?? '') !== 'completed') {
                                $dueDate = new DateTime($task['due_date']);
                                if ($dueDate < $now) {
                                    $isOverdue = true;
                                }
                            }
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-150 task-row" 
                            data-project="<?php echo $task['project_id']; ?>"
                            data-name="<?php echo strtolower(htmlspecialchars($task['task_name'])); ?>"
                            data-project-name="<?php echo strtolower(htmlspecialchars($task['project_name'] ?? '')); ?>">
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($task['task_name']); ?></div>
                                <?php if (!empty($task['description'])): ?>
                                <div class="text-xs text-gray-500 truncate max-w-xs"><?php echo htmlspecialchars($task['description']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($task['project_name'] ?? '-'); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($task['phase_name'] ?? 'No Phase'); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($task['assignee_name'] ?? 'Unassigned'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($task['due_date'])): ?>
                                <div class="text-sm <?php echo $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-900'; ?>">
                                    <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                </div>
                                <?php if ($isOverdue): ?>
                                <div class="text-xs text-red-500">Overdue</div>
                                <?php endif; ?>
                                <?php else: ?>
                                <span class="text-sm text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                    $statusClass = match(strtolower($task['status'] ?? '')) {
                                        'not started' => 'bg-yellow-100 text-yellow-700',
                                        'in progress' => 'bg-green-100 text-green-700',
                                        'completed' => 'bg-purple-100 text-purple-700',
                                        'on hold' => 'bg-red-100 text-red-700',
                                        default => 'bg-gray-100 text-gray-700'
                                    };
                                ?>
                                <span class="px-3 py-1 text-xs font-bold rounded-full <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($task['status'] ?? 'Unknown'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                    $priorityClass = match(strtolower($task['priority'] ?? '')) {
                                        'low' => 'bg-gray-100 text-gray-700',
                                        'medium' => 'bg-blue-100 text-blue-700',
                                        'high' => 'bg-orange-100 text-orange-700',
                                        'urgent' => 'bg-red-100 text-red-700',
                                        default => 'bg-gray-100 text-gray-700'
                                    };
                                ?>
                                <span class="px-3 py-1 text-xs font-bold rounded-full <?php echo $priorityClass; ?>">
                                    <?php echo htmlspecialchars($task['priority'] ?? 'Medium'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button class="edit-btn text-gray-500 p-2 rounded-lg hover:text-green-600 hover:bg-green-50 transition-colors duration-200" 
                                            data-id="<?php echo $task['task_id']; ?>" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    
                                    <button class="delete-btn text-gray-500 p-2 rounded-lg hover:text-red-600 hover:bg-red-50 transition-colors duration-200" 
                                            data-id="<?php echo $task['task_id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($task['task_name'], ENT_QUOTES); ?>" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <p class="text-sm text-gray-500" id="paginationInfo">Showing 0-0 of 0</p>
                <div class="flex items-center gap-2">
                    <button id="prevPage" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Prev
                    </button>
                    <button id="nextPage" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Task Modal -->
    <?php include __DIR__ . '/components/task_modal.php'; ?>

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
                            <h3 class="text-2xl font-bold text-white">Delete Task</h3>
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
                <p class="text-gray-700 text-lg font-medium mb-6">Are you sure you want to delete this task?</p>
                
                <div class="bg-red-50 border-2 border-red-200 rounded-xl p-5">
                    <div class="flex items-start gap-4">
                        <div class="bg-red-100 rounded-full p-2 shrink-0">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-red-900 mb-1.5 uppercase tracking-wide">Task Name</p>
                            <p id="deleteTaskName" class="text-lg font-bold text-red-700 bg-white px-3 py-2 rounded-lg border border-red-200"></p>
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
                    Delete Task
                </button>
            </div>
        </div>
    </div>

    <script>
        const backendUrl = "api/tasks.php";
        const projectsData = <?php echo json_encode($allProjects); ?>;
        const phasesData = <?php echo json_encode($allPhases); ?>;
        const employeesData = <?php echo json_encode($allEmployees); ?>;
        let taskToDelete = null;
        
        // Pagination
        let currentPage = 1;
        const itemsPerPage = 10;
        let filteredRows = [];

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateTable();
        });

        // Search and Filter
        document.getElementById('searchInput')?.addEventListener('input', updateTable);
        document.getElementById('projectFilter')?.addEventListener('change', updateTable);

        function updateTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const projectFilter = document.getElementById('projectFilter').value;
            const rows = document.querySelectorAll('.task-row');
            
            filteredRows = [];
            rows.forEach(row => {
                const name = row.dataset.name || '';
                const projectName = row.dataset.projectName || '';
                const projectId = row.dataset.project;
                
                const matchesSearch = name.includes(searchTerm) || projectName.includes(searchTerm);
                const matchesProject = !projectFilter || projectId === projectFilter;
                
                if (matchesSearch && matchesProject) {
                    filteredRows.push(row);
                }
            });
            
            currentPage = 1;
            renderPage();
        }

        function renderPage() {
            const rows = document.querySelectorAll('.task-row');
            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            
            rows.forEach(row => row.style.display = 'none');
            
            filteredRows.forEach((row, index) => {
                if (index >= start && index < end) {
                    row.style.display = '';
                }
            });
            
            // Update pagination info
            const total = filteredRows.length;
            const showStart = total > 0 ? start + 1 : 0;
            const showEnd = Math.min(end, total);
            document.getElementById('paginationInfo').textContent = `Showing ${showStart}-${showEnd} of ${total}`;
            
            // Update buttons
            document.getElementById('prevPage').disabled = currentPage === 1;
            document.getElementById('nextPage').disabled = end >= total;
        }

        document.getElementById('prevPage')?.addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage--;
                renderPage();
            }
        });

        document.getElementById('nextPage')?.addEventListener('click', function() {
            const maxPage = Math.ceil(filteredRows.length / itemsPerPage);
            if (currentPage < maxPage) {
                currentPage++;
                renderPage();
            }
        });

        // Populate project select in modal
        function populateProjectSelect(selectedProjectId = null) {
            const select = document.getElementById('task_project_id');
            if (!select) return;
            select.innerHTML = '<option value="">Select Project</option>';
            projectsData.forEach(proj => {
                const selected = selectedProjectId == proj.project_id ? 'selected' : '';
                select.innerHTML += `<option value="${proj.project_id}" ${selected}>${proj.project_name}</option>`;
            });
        }

        // Populate phase select based on project
        function populatePhaseSelect(selectedProjectId = null, selectedPhaseId = null) {
            const select = document.getElementById('task_phase_id');
            if (!select) return;
            select.innerHTML = '<option value="">Select Phase (Optional)</option>';
            
            phasesData.forEach(phase => {
                if (!selectedProjectId || phase.project_id == selectedProjectId) {
                    const selected = selectedPhaseId == phase.phase_id ? 'selected' : '';
                    select.innerHTML += `<option value="${phase.phase_id}" ${selected}>${phase.phase_name} (${phase.project_name})</option>`;
                }
            });
        }

        // Populate assignee select
        function populateAssigneeSelect(selectedEmployeeId = null) {
            const select = document.getElementById('task_assignee');
            if (!select) return;
            select.innerHTML = '<option value="">Unassigned</option>';
            employeesData.forEach(emp => {
                const fullName = emp.first_name + ' ' + emp.last_name;
                const selected = selectedEmployeeId == emp.employee_id ? 'selected' : '';
                select.innerHTML += `<option value="${emp.employee_id}" ${selected}>${fullName} (${emp.employee_code})</option>`;
            });
        }

        // Update phase dropdown when project changes
        document.getElementById('task_project_id')?.addEventListener('change', function() {
            populatePhaseSelect(this.value);
        });

        // ------------------ Add Task ------------------
        document.getElementById('addTaskBtn')?.addEventListener('click', function() {
            document.getElementById('taskForm').reset();
            document.getElementById('taskModalTitle').textContent = 'Add Task';
            document.getElementById('taskModalBtnText').textContent = 'Add Task';
            document.getElementById('task_id').value = '';
            populateProjectSelect();
            populatePhaseSelect();
            populateAssigneeSelect();
            document.getElementById('taskModal').style.display = 'flex';
        });

        // ------------------ Edit Task ------------------
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const taskId = this.dataset.id;

                fetch(backendUrl + '?fetch_id=' + taskId)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const task = data.task;
                            document.getElementById('task_id').value = task.task_id;
                            populateProjectSelect(task.project_id);
                            populatePhaseSelect(task.project_id, task.phase_id);
                            populateAssigneeSelect(task.assigned_to_employee_id);
                            document.getElementById('task_name').value = task.task_name;
                            document.getElementById('task_description').value = task.description || '';
                            document.getElementById('task_start_date').value = task.start_date || '';
                            document.getElementById('task_due_date').value = task.due_date || '';
                            document.getElementById('task_priority').value = task.priority || 'Medium';
                            document.getElementById('task_status').value = task.status || 'Not Started';
                            
                            document.getElementById('taskModalTitle').textContent = 'Edit Task';
                            document.getElementById('taskModalBtnText').textContent = 'Save Changes';
                            document.getElementById('taskModal').style.display = 'flex';
                        } else {
                            showToast(data.message || 'Error fetching task', 'error');
                        }
                    });
            });
        });

        // ------------------ Delete Task ------------------
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                taskToDelete = this.dataset.id;
                document.getElementById('deleteTaskName').textContent = this.dataset.name;
                document.getElementById('deleteModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            });
        });

        function closeDeleteModal() {
            taskToDelete = null;
            document.getElementById('deleteModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function confirmDelete() {
            if (!taskToDelete) return;

            const btn = document.getElementById('confirmDeleteBtn');
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Deleting...`;

            const formData = new FormData();
            formData.append('delete_id', taskToDelete);

            fetch(backendUrl, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Task deleted successfully', 'success', true);
                        closeDeleteModal();
                        setTimeout(() => window.location.reload(), 300);
                    } else {
                        showToast(data.message || 'Error deleting task', 'error');
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                    }
                })
                .catch(err => {
                    showToast('Error deleting task: ' + err.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                });
        }

        // Close modal on outside click
        document.getElementById('deleteModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('deleteModal').classList.contains('hidden')) {
                closeDeleteModal();
            }
        });

        // ------------------ Close Task Modal ------------------
        function closeTaskModal() {
            document.getElementById('taskForm').reset();
            document.getElementById('taskModal').style.display = 'none';
        }
        document.getElementById('closeTaskModal')?.addEventListener('click', closeTaskModal);
        document.getElementById('cancelTaskModal')?.addEventListener('click', closeTaskModal);

        // ------------------ Submit Add/Edit ------------------
        document.getElementById('taskForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);

            fetch(backendUrl, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Task saved successfully', 'success', true);
                        closeTaskModal();
                        setTimeout(() => window.location.reload(), 300);
                    } else {
                        showToast(data.message || 'Error saving task', 'error');
                    }
                })
                .catch(err => {
                    showToast('Error: ' + err.message, 'error');
                });
        });
    </script>
</body>
</html>
