<?php
// modules/project/phases.php

// 1. Configuration (Session & Constants)
require_once __DIR__ . '/../../config/config.php';

// 2. Authentication Check
if (!isset($_SESSION['user_id'])) { 
    header("Location: " . BASE_URL . "index.php"); 
    exit(); 
}

// 3. Database Connection
require_once __DIR__ . '/../../config/database.php';

// Default Phases for new projects
$defaultPhases = [
    'Phase 1: Mobilization',
    'Phase 2: Structural',
    'Phase 3: MEPFS (Mechanical, Electrical, Plumbing, Fire Protection, and Sanitary)',
    'Phase 4: Finishing'
];

// Fetch all phases across all projects with approved budget from budget_proposals
$sql = "SELECT ph.*, p.project_name, p.project_code,
           COALESCE(SUM(CASE WHEN bp.status = 'APPROVED' THEN bp.total_amount ELSE 0 END), 0) AS phase_budget
    FROM icmis_project_phases ph 
    LEFT JOIN icmis_projects p ON ph.project_id = p.project_id 
    LEFT JOIN budget_proposals bp ON bp.phase_id = ph.phase_id AND bp.status = 'APPROVED'
    GROUP BY ph.phase_id
    ORDER BY ph.phase_id DESC, ph.start_date DESC";
$result = $conn->query($sql);

$phases = [];
$totalPhases = 0;
$activeCount = 0;
$completedCount = 0;
$upcomingCount = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $phases[] = $row;
        $totalPhases++;
        
        $status = strtolower($row['status'] ?? '');
        if ($status === 'in progress') $activeCount++;
        if ($status === 'completed') $completedCount++;
        if ($status === 'not started') $upcomingCount++;
    }
}

// Fetch all projects for dropdown filter
$projectsResult = $conn->query("SELECT project_id, project_name, project_code FROM icmis_projects ORDER BY project_name ASC");
$allProjects = [];
while ($row = $projectsResult->fetch_assoc()) {
    $allProjects[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phase Management | ICMIS</title>
    
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
        $pageTitle = "Phase Management";
        $pageSection = "Project Management";
        $pageSubTitle = '<span class="text-sm text-gray-500">Define and manage project phases and milestones</span>';
        include __DIR__ . '/../../includes/header.php'; 
    ?>

    <main class="ml-56 mt-16 p-6">
        <div class="max-w-7xl mx-auto">
            
            <div class="flex items-center gap-1 mb-6 border-b border-gray-200">
                <a href="projects.php" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                    Projects
                </a>
                <a href="phases.php" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-[#e9922c] text-[#e9922c] transition-colors">
                    Phases
                </a>
                <a href="tasks.php" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                    Tasks
                </a>
                <a href="gantt.php" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                    Gantt Chart
                </a>
            </div>

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl text-gray-900 font-bold">Phase Management</h1>
                <p class="text-sm text-gray-500 mt-1">Define and manage project phases and milestones.</p>
            </div>
        </div>

        <div class="flex items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-4 flex-1">
                <div class="relative flex-1 max-w-md">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" id="searchInput" placeholder="Search phase or project" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] text-sm">
                </div>
                
                <select id="projectFilter" class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c]">
                    <option value="">Filter</option>
                    <?php foreach ($allProjects as $proj): ?>
                    <option value="<?php echo $proj['project_id']; ?>"><?php echo htmlspecialchars($proj['project_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button id="addPhaseBtn" class="flex items-center gap-2 bg-[#e9922c] text-white px-6 py-2.5 rounded-lg hover:bg-[#d17f1f] transition-colors duration-200 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="font-medium">Add Phase</span>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Phases</p>
                        <h2 id="statTotalPhases" class="text-2xl font-bold text-gray-900"><?php echo $totalPhases; ?></h2>
                        <p class="text-xs text-gray-400">Across all projects</p>
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
                        <p class="text-sm text-gray-500">Active Phases</p>
                        <h2 id="statActivePhases" class="text-2xl font-bold text-gray-900"><?php echo $activeCount; ?></h2>
                        <p class="text-xs text-gray-400">Currently in progress</p>
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
                        <p class="text-sm text-gray-500">Completed Phases</p>
                        <h2 id="statCompletedPhases" class="text-2xl font-bold text-gray-900"><?php echo $completedCount; ?></h2>
                        <p class="text-xs text-gray-400">Successfully finished</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="bg-yellow-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Upcoming Phases</p>
                        <h2 id="statUpcomingPhases" class="text-2xl font-bold text-gray-900"><?php echo $upcomingCount; ?></h2>
                        <p class="text-xs text-gray-400">Scheduled to start</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="phasesTableContainer" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full" id="phasesTable">
                    <thead>
                        <tr class="bg-gradient-to-r from-slate-800 to-slate-700">
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Phase Name</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Project</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Description</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Start Date</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Priority</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Budget</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-white uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" id="phasesTableBody">
                        <?php if (empty($phases)): ?>
                        <tr id="emptyRow">
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                    </svg>
                                    <p class="text-sm">No phases found</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($phases as $phase): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-150 phase-row" 
                            data-project="<?php echo $phase['project_id']; ?>"
                            data-name="<?php echo strtolower(htmlspecialchars($phase['phase_name'])); ?>"
                            data-project-name="<?php echo strtolower(htmlspecialchars($phase['project_name'] ?? '')); ?>">
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($phase['phase_name']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($phase['project_name'] ?? '-'); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($phase['project_code'] ?? ''); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-600 max-w-xs truncate"><?php echo htmlspecialchars($phase['description'] ?? '-'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo !empty($phase['start_date']) ? date('M d, Y', strtotime($phase['start_date'])) : '-'; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                    $statusClass = match(strtolower($phase['status'] ?? '')) {
                                        'not started' => 'bg-yellow-100 text-yellow-700',
                                        'in progress' => 'bg-green-100 text-green-700',
                                        'completed' => 'bg-purple-100 text-purple-700',
                                        default => 'bg-gray-100 text-gray-700'
                                    };
                                ?>
                                <span class="px-3 py-1 text-xs font-bold rounded-full <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($phase['status'] ?? 'Unknown'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 text-xs font-bold rounded-full bg-blue-100 text-blue-700">Medium</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                $phaseBudget = floatval($phase['phase_budget'] ?? 0);
                                if ($phaseBudget > 0): 
                                ?>
                                <span class="text-sm font-semibold text-gray-900">₱<?php echo number_format($phaseBudget, 2); ?></span>
                                <?php else: ?>
                                <span class="text-sm text-gray-400 italic">No budget</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button class="edit-btn text-gray-500 p-2 rounded-lg hover:text-green-600 hover:bg-green-50 transition-colors duration-200" 
                                            data-id="<?php echo $phase['phase_id']; ?>" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    
                                    <button class="delete-btn text-gray-500 p-2 rounded-lg hover:text-red-600 hover:bg-red-50 transition-colors duration-200" 
                                            data-id="<?php echo $phase['phase_id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($phase['phase_name'], ENT_QUOTES); ?>" title="Delete">
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

    <?php include __DIR__ . '/components/phase_modal.php'; ?>

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
                            <h3 class="text-2xl font-bold text-white">Delete Phase</h3>
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
                <p class="text-gray-700 text-lg font-medium mb-2">Are you sure you want to delete this phase?</p>
                <p class="text-gray-500 text-sm mb-6">This will also delete all tasks associated with this phase.</p>
                
                <div class="bg-red-50 border-2 border-red-200 rounded-xl p-5">
                    <div class="flex items-start gap-4">
                        <div class="bg-red-100 rounded-full p-2 shrink-0">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-red-900 mb-1.5 uppercase tracking-wide">Phase Name</p>
                            <p id="deletePhaseName" class="text-lg font-bold text-red-700 bg-white px-3 py-2 rounded-lg border border-red-200"></p>
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
                    Delete Phase
                </button>
            </div>
        </div>
    </div>
        
    <script>
        const backendUrl = "api/phases.php";
        const projectsData = <?php echo json_encode($allProjects); ?>;
    </script>
    <script src="js/phases.js"></script>
</body>
</html>