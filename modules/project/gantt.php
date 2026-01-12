<?php
// modules/project/gantt.php - Gantt Chart View

// 1. Configuration (Session & Constants)
require_once __DIR__ . '/../../config/config.php';

// 2. Authentication Check
if (!isset($_SESSION['user_id'])) { 
    header("Location: " . BASE_URL . "index.php"); 
    exit(); 
}

// 3. Database Connection
require_once __DIR__ . '/../../config/database.php';

// Fetch all projects for filter dropdown
$projectsResult = $conn->query("SELECT project_id, project_name, project_code FROM icmis_projects ORDER BY project_name ASC");
$allProjects = [];
while ($row = $projectsResult->fetch_assoc()) {
    $allProjects[] = $row;
}

// Get selected project filter
$selectedProjectId = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

// Build WHERE clause for project filter
$projectFilter = $selectedProjectId > 0 ? " WHERE p.project_id = $selectedProjectId" : "";
$projectFilterPhases = $selectedProjectId > 0 ? " WHERE ph.project_id = $selectedProjectId" : "";
$projectFilterTasks = $selectedProjectId > 0 ? " AND t.project_id = $selectedProjectId" : "";

// Fetch phases for Gantt chart
$phasesSql = "SELECT ph.phase_id, ph.phase_name, ph.start_date, ph.end_date, 
                     p.project_name, p.project_id
              FROM icmis_project_phases ph
              LEFT JOIN icmis_projects p ON ph.project_id = p.project_id
              $projectFilterPhases
              ORDER BY ph.start_date ASC, ph.phase_id ASC";
$phasesResult = $conn->query($phasesSql);

$phases = [];
while ($row = $phasesResult->fetch_assoc()) {
    $phases[] = $row;
}

// Fetch ALL tasks for Gantt chart (including those without dates)
$tasksSql = "SELECT t.task_id, t.task_name, t.start_date, t.due_date, t.status, t.priority,
                    p.project_name, p.project_id, p.start_date as project_start, p.end_date as project_end,
                    ph.phase_name, ph.start_date as phase_start, ph.end_date as phase_end
             FROM icmis_tasks t
             LEFT JOIN icmis_projects p ON t.project_id = p.project_id
             LEFT JOIN icmis_project_phases ph ON t.phase_id = ph.phase_id
             WHERE 1=1
             $projectFilterTasks
             ORDER BY COALESCE(t.start_date, ph.start_date, p.start_date, CURDATE()) ASC, t.task_id ASC";
$tasksResult = $conn->query($tasksSql);

$tasks = [];
while ($row = $tasksResult->fetch_assoc()) {
    $tasks[] = $row;
}

// Prepare Gantt data in JSON format
$ganttData = [];

// Add phases (Orange bars)
foreach ($phases as $phase) {
    if (!empty($phase['start_date']) && !empty($phase['end_date'])) {
        $ganttData[] = [
            'id' => 'phase_' . $phase['phase_id'],
            'name' => $phase['phase_name'],
            'start' => $phase['start_date'],
            'end' => $phase['end_date'],
            'progress' => 0,
            'type' => 'phase',
            'project' => $phase['project_name'],
            'custom_class' => 'bar-phase'
        ];
    }
}

// Add tasks (Blue bars) - use fallback dates if task dates are missing
foreach ($tasks as $task) {
    // Fallback chain: task date -> phase date -> project date -> current date
    $startDate = $task['start_date'] 
                 ?? $task['due_date'] 
                 ?? $task['phase_start'] 
                 ?? $task['project_start'] 
                 ?? date('Y-m-d');
    
    $endDate = $task['due_date'] 
               ?? $task['start_date'] 
               ?? $task['phase_end'] 
               ?? $task['project_end'] 
               ?? date('Y-m-d', strtotime('+7 days'));
    
    // Ensure end date is after start date
    if (strtotime($endDate) < strtotime($startDate)) {
        $endDate = date('Y-m-d', strtotime($startDate . ' +7 days'));
    }
    
    // Calculate progress based on status
    $progress = match(strtolower($task['status'] ?? '')) {
        'not started' => 0,
        'in progress' => 50,
        'on hold' => 25,
        'completed' => 100,
        default => 0
    };
    
    $ganttData[] = [
        'id' => 'task_' . $task['task_id'],
        'name' => $task['task_name'],
        'start' => $startDate,
        'end' => $endDate,
        'progress' => $progress,
        'type' => 'task',
        'project' => $task['project_name'] ?? 'No Project',
        'phase' => $task['phase_name'] ?? '',
        'status' => $task['status'],
        'priority' => $task['priority'],
        'custom_class' => 'bar-task',
        'has_dates' => !empty($task['start_date']) || !empty($task['due_date'])
    ];
}

// Sort by start date
usort($ganttData, function($a, $b) {
    return strtotime($a['start']) - strtotime($b['start']);
});

$ganttDataJson = json_encode($ganttData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gantt Chart | ICMIS</title>
    
    <?php include __DIR__ . '/../../includes/head_assets.php'; ?>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.css">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        
        /* Custom Gantt Styles */
        .gantt-container {
            overflow-x: auto;
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }
        
        .gantt .bar-wrapper {
            cursor: pointer;
        }
        
        /* Phase bars - Orange */
        .gantt .bar-phase .bar {
            fill: #e9922c !important;
        }
        .gantt .bar-phase .bar-progress {
            fill: #d17f1f !important;
        }
        .gantt .bar-phase .bar-label {
            fill: white !important;
            font-weight: 600 !important;
        }
        
        /* Task bars - Blue */
        .gantt .bar-task .bar {
            fill: #3b82f6 !important;
        }
        .gantt .bar-task .bar-progress {
            fill: #2563eb !important;
        }
        .gantt .bar-task .bar-label {
            fill: white !important;
            font-weight: 500 !important;
        }
        
        /* Grid styling */
        .gantt .grid-header {
            fill: #1e293b !important;
        }
        .gantt .grid-header text {
            fill: white !important;
            font-weight: 600 !important;
        }
        
        .gantt .lower-text, .gantt .upper-text {
            font-family: 'Inter', sans-serif !important;
            font-size: 11px !important;
            fill: white !important;
            font-weight: bold !important;
        }
        
        .gantt .bar-label {
            font-family: 'Inter', sans-serif !important;
            font-size: 11px !important;
            transition: fill .15s ease;
        }
        
        /* Today line */
        .gantt .today-highlight {
            fill: #e9922c !important;
            opacity: 0.1 !important;
        }
        
        /* Popup styling */
        .gantt .popup-wrapper {
            background: white !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
            border: 1px solid #e5e7eb !important;
        }
        
        .gantt .title {
            font-family: 'Inter', sans-serif !important;
            font-weight: 600 !important;
            color: #1f2937 !important;
        }
        
        .gantt .subtitle {
            font-family: 'Inter', sans-serif !important;
            color: #6b7280 !important;
        }
        
        /* View mode buttons */
        .view-mode-btn {
            transition: all 0.2s ease;
        }
        .view-mode-btn.active {
            background-color: #e9922c !important;
            color: white !important;
        }
        
        /* Empty state */
        .empty-gantt {
            min-height: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php 
        include __DIR__ . '/../../includes/sidebar.php';
        include __DIR__ . '/../../includes/toast.php';
    ?>
    
    <?php
        $pageTitle = "Gantt Chart";
        $pageSection = "Project Operations";
        $pageSubTitle = '<span class="text-sm text-gray-500">Visualize project timeline and task schedule</span>';
        include __DIR__ . '/../../includes/header.php'; 
    ?>

    <main class="ml-56 mt-16 p-6 transition-all duration-300 animate-fade-in">
        <div class="max-w-full mx-auto">
            
            <div class="flex items-center gap-1 mb-6 border-b border-gray-200">
                <a href="projects.php" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                    Projects
                </a>
                <a href="phases.php" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                    Phases
                </a>
                <a href="tasks.php" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                    Tasks
                </a>
                <a href="gantt.php" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-[#e9922c] text-[#e9922c] transition-colors">
                    Gantt Chart
                </a>
            </div>

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl text-gray-900 font-bold">Project Timeline</h1>
                    <p class="text-sm text-gray-500 mt-1">View phases and tasks on a timeline</p>
                </div>
                
                <div class="flex items-center gap-4">
                    <select id="projectFilter" onchange="filterByProject(this.value)" 
                            class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] min-w-[200px]">
                        <option value="">All Projects</option>
                        <?php foreach ($allProjects as $proj): ?>
                        <option value="<?php echo $proj['project_id']; ?>" <?php echo $selectedProjectId == $proj['project_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($proj['project_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div class="flex items-center bg-gray-100 rounded-lg p-1">
                        <button onclick="changeViewMode('Day')" class="view-mode-btn px-3 py-1.5 text-sm font-medium text-gray-600 rounded-md hover:bg-white transition-colors">
                            Day
                        </button>
                        <button onclick="changeViewMode('Week')" class="view-mode-btn active px-3 py-1.5 text-sm font-medium text-gray-600 rounded-md hover:bg-white transition-colors">
                            Week
                        </button>
                        <button onclick="changeViewMode('Month')" class="view-mode-btn px-3 py-1.5 text-sm font-medium text-gray-600 rounded-md hover:bg-white transition-colors">
                            Month
                        </button>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-3">
                        <div class="bg-orange-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Total Phases</p>
                            <h2 id="statTotalPhases" class="text-2xl font-bold text-gray-900"><?php echo count($phases); ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Total Tasks</p>
                            <h2 id="statTotalTasks" class="text-2xl font-bold text-gray-900"><?php echo count($tasks); ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-3">
                        <div class="bg-purple-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Projects</p>
                            <h2 id="statTotalProjects" class="text-2xl font-bold text-gray-900"><?php echo count($allProjects); ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex items-center gap-3">
                        <div class="bg-green-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Timeline Items</p>
                            <h2 id="statTimelineItems" class="text-2xl font-bold text-gray-900"><?php echo count($ganttData); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-6 mb-4 mt-6">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded bg-[#e9922c]"></div>
                    <span class="text-sm text-gray-600">Phases</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded bg-blue-500"></div>
                    <span class="text-sm text-gray-600">Tasks</span>
                </div>
                <div class="flex items-center gap-2 ml-4 pl-4 border-l border-gray-300">
                    <span class="text-xs text-gray-500">Progress bar shows completion status</span>
                </div>
            </div>

            <div id="ganttChartWrapper">
                <?php if (empty($ganttData)): ?>
                <div class="gantt-container empty-gantt">
                    <div class="text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">No Timeline Data</h3>
                        <p class="text-sm text-gray-500 max-w-md">
                            Add start and end dates to your phases and tasks to see them on the Gantt chart.
                        </p>
                        <div class="flex items-center justify-center gap-3 mt-6">
                            <a href="phases.php" class="px-4 py-2 bg-[#e9922c] text-white rounded-lg hover:bg-[#d17f1f] transition-colors text-sm font-medium">
                                Manage Phases
                            </a>
                            <a href="tasks.php" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm font-medium">
                                Manage Tasks
                            </a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="gantt-container p-4">
                    <svg id="gantt"></svg>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>

    <script id="gantt-data" type="application/json"><?php echo $ganttDataJson; ?></script>

    <script src="js/gantt.js"></script>
</body>
</html>