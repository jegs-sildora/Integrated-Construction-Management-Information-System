<?php
// modules/project/tasks.php - Kanban Board View

// 1. Configuration (Session & Constants)
require_once __DIR__ . '/../../config/config.php';

// 2. Authentication Check
if (!isset($_SESSION['user_id'])) { 
    header("Location: " . BASE_URL . "index.php"); 
    exit(); 
}

// 3. Database Connection
require_once __DIR__ . '/../../config/database.php';

// Define status columns for Kanban
$statusColumns = [
    'Not Started' => [],
    'In Progress' => [],
    'On Hold' => [],
    'Completed' => []
];

// Fetch all tasks and group by status
$sql = "SELECT t.*, 
               p.project_name, p.project_code,
               ph.phase_name,
               CONCAT(e.first_name, ' ', e.last_name) as assignee_name,
               SUBSTRING(e.first_name, 1, 1) as assignee_initial_first,
               SUBSTRING(e.last_name, 1, 1) as assignee_initial_last
        FROM icmis_tasks t 
        LEFT JOIN icmis_projects p ON t.project_id = p.project_id
        LEFT JOIN icmis_project_phases ph ON t.phase_id = ph.phase_id
        LEFT JOIN workforce_employees e ON t.assigned_to_employee_id = e.employee_id
        ORDER BY t.priority DESC, t.due_date ASC";
$result = $conn->query($sql);

$totalTasks = 0;
$overdueCount = 0;
$now = new DateTime();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $status = $row['status'] ?? 'Not Started';
        if (isset($statusColumns[$status])) {
            $statusColumns[$status][] = $row;
        } else {
            $statusColumns['Not Started'][] = $row;
        }
        $totalTasks++;
        
        // Check if overdue
        if (!empty($row['due_date']) && strtolower($row['status']) !== 'completed') {
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

// Status column colors
$statusColors = [
    'Not Started' => ['bg' => 'bg-slate-100', 'border' => 'border-slate-300', 'header' => 'bg-slate-500', 'badge' => 'bg-slate-200 text-slate-700'],
    'In Progress' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-300', 'header' => 'bg-blue-500', 'badge' => 'bg-blue-200 text-blue-700'],
    'On Hold' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-300', 'header' => 'bg-amber-500', 'badge' => 'bg-amber-200 text-amber-700'],
    'Completed' => ['bg' => 'bg-green-50', 'border' => 'border-green-300', 'header' => 'bg-green-500', 'badge' => 'bg-green-200 text-green-700']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management - Kanban | ICMIS</title>
    
    <?php include __DIR__ . '/../../includes/head_assets.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        @keyframes modal-slide-in {
            from { opacity: 0; transform: translateY(-20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .animate-modal-slide-in { animation: modal-slide-in 0.3s ease-out forwards; }
        
        /* Kanban specific styles */
        .kanban-board {
            height: 65vh;
        }
        .kanban-column {
            min-height: 0;
            flex: 1 1 0;
        }
        .task-card {
            cursor: grab;
            transition: all 0.2s ease;
        }
        .task-card:active {
            cursor: grabbing;
        }
        .task-card.sortable-ghost {
            opacity: 0.4;
            background: #fef3c7;
        }
        .task-card.sortable-chosen {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
            transform: rotate(2deg);
        }
        .kanban-scroll {
            scrollbar-width: thin;
            scrollbar-color: #d1d5db #f3f4f6;
        }
        .kanban-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .kanban-scroll::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 3px;
        }
        .kanban-scroll::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 3px;
        }
        .kanban-scroll::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
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
        $pageSubTitle = '<span class="text-sm text-gray-500">Kanban board for task management</span>';
        include __DIR__ . '/../../includes/header.php'; 
    ?>

    <main class="ml-56 mt-16 p-6">
        <div class="max-w-full mx-auto">
            
            <div class="flex items-center gap-1 mb-6 border-b border-gray-200">
                <a href="projects.php" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                    Projects
                </a>
                <a href="phases.php" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                    Phases
                </a>
                <a href="tasks.php" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-[#e9922c] text-[#e9922c] transition-colors">
                    Tasks
                </a>
                <a href="gantt.php" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                    Gantt Chart
                </a>
            </div>

            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-6">
                    <div>
                        <h1 class="text-2xl text-gray-900 font-bold">Task Board</h1>
                        <p class="text-sm text-gray-500 mt-1">Drag and drop tasks to update their status</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 px-4 py-2 bg-white rounded-lg border border-gray-200">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <span class="text-sm font-semibold text-gray-700"><span id="statTotalTasks"><?php echo $totalTasks; ?></span> Tasks</span>
                        </div>
                        
                        <div id="statOverdueWrapper" class="<?php echo ($overdueCount > 0) ? 'flex' : 'hidden'; ?> items-center gap-2 px-4 py-2 bg-red-50 rounded-lg border border-red-200">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm font-semibold text-red-700"><span id="statOverdueTasks"><?php echo $overdueCount; ?></span> Overdue</span>
                        </div>
                    </div>
                </div>
                
                <button id="addTaskBtn" class="flex items-center gap-2 bg-[#e9922c] text-white px-6 py-2.5 rounded-lg hover:bg-[#d17f1f] transition-colors duration-200 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="font-medium">Add Task</span>
                </button>
            </div>

            <div id="kanbanBoardContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 kanban-board">
                <?php foreach ($statusColumns as $status => $tasks): ?>
                <?php $colors = $statusColors[$status]; ?>
                <div class="flex flex-col">
                    <div class="<?php echo $colors['header']; ?> rounded-t-xl px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-white text-sm uppercase tracking-wide"><?php echo $status; ?></h3>
                            <span class="bg-white/20 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?php echo count($tasks); ?></span>
                        </div>
                    </div>
                    
                    <div class="kanban-column <?php echo $colors['bg']; ?> border-l border-r border-b <?php echo $colors['border']; ?> rounded-b-xl p-3 kanban-scroll overflow-y-auto" 
                         data-status="<?php echo htmlspecialchars($status); ?>" 
                         id="column-<?php echo str_replace(' ', '-', strtolower($status)); ?>">
                        
                        <?php if (empty($tasks)): ?>
                        <div class="empty-state text-center py-8 text-gray-400">
                            <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <p class="text-sm">No tasks</p>
                        </div>
                        <?php endif; ?>
                        <?php foreach ($tasks as $task): ?>
                        <?php
                            $isOverdue = false;
                            if (!empty($task['due_date']) && strtolower($task['status']) !== 'completed') {
                                $dueDate = new DateTime($task['due_date']);
                                if ($dueDate < $now) {
                                    $isOverdue = true;
                                }
                            }
                            
                            $priorityClass = match(strtolower($task['priority'] ?? '')) {
                                'low' => 'bg-gray-100 text-gray-600',
                                'medium' => 'bg-blue-100 text-blue-700',
                                'high' => 'bg-orange-100 text-orange-700',
                                'urgent' => 'bg-red-100 text-red-700',
                                default => 'bg-gray-100 text-gray-600'
                            };
                            
                            $initials = strtoupper(($task['assignee_initial_first'] ?? '') . ($task['assignee_initial_last'] ?? ''));
                        ?>
                        <div class="task-card group bg-white rounded-lg p-3 mb-2 shadow-sm border border-gray-200 hover:shadow-md transition-shadow" 
                             data-id="<?php echo $task['task_id']; ?>">
                            <div class="flex items-start justify-between mb-2">
                                <h4 class="font-semibold text-gray-900 text-sm leading-tight flex-1 pr-2"><?php echo htmlspecialchars($task['task_name']); ?></h4>
                                <span class="<?php echo $priorityClass; ?> text-xs font-bold px-2 py-0.5 rounded shrink-0">
                                    <?php echo $task['priority'] ?? 'Medium'; ?>
                                </span>
                            </div>
                            
                            <p class="text-xs text-gray-500 mb-2 truncate">
                                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                                <?php echo htmlspecialchars($task['project_name'] ?? 'No Project'); ?>
                            </p>
                            
                            <div class="flex items-center justify-between mt-3 pt-2 border-t border-gray-100">
                                <div class="flex items-center gap-1 <?php echo $isOverdue ? 'text-red-600' : 'text-gray-400'; ?>">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-xs font-medium">
                                        <?php echo !empty($task['due_date']) ? date('M d', strtotime($task['due_date'])) : 'No date'; ?>
                                    </span>
                                </div>
                                
                                <?php if (!empty($task['assignee_name'])): ?>
                                <div class="flex items-center gap-1.5" title="<?php echo htmlspecialchars($task['assignee_name']); ?>">
                                    <div class="w-6 h-6 rounded-full bg-[#e9922c] flex items-center justify-center">
                                        <span class="text-white text-xs font-bold"><?php echo $initials; ?></span>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center" title="Unassigned">
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="hidden group-hover:flex items-center justify-end gap-1 mt-2 pt-2 border-t border-gray-100">
                                <button class="edit-btn p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded transition-colors" 
                                        data-id="<?php echo $task['task_id']; ?>" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button class="delete-btn p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors" 
                                        data-id="<?php echo $task['task_id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($task['task_name'], ENT_QUOTES); ?>" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/components/task_modal.php'; ?>

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
        const updateStatusUrl = "api/update_task_status.php";
        const projectsData = <?php echo json_encode($allProjects); ?>;
        const phasesData = <?php echo json_encode($allPhases); ?>;
        const employeesData = <?php echo json_encode($allEmployees); ?>;
    </script>
    <script src="js/tasks.js"></script>
</body>
</html>