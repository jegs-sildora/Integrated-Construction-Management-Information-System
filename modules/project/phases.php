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

// Get project_id from query string
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

// Fetch project details
$project = null;
if ($projectId > 0) {
    $stmt = $conn->prepare("SELECT p.*, CONCAT(e.first_name, ' ', e.last_name) AS manager_name
                            FROM icmis_projects p 
                            LEFT JOIN workforce_employees e ON p.project_manager_id = e.employee_id 
                            WHERE p.project_id = ?");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();
    $stmt->close();
}

// Fetch phases for this project
$phases = [];
$totalPhases = 0;
$pendingCount = 0;
$inProgressCount = 0;
$completedCount = 0;

if ($project) {
    $sql = "SELECT ph.*, 
                   (SELECT COUNT(*) FROM icmis_tasks t WHERE t.phase_id = ph.phase_id) as task_count
            FROM icmis_project_phases ph 
            WHERE ph.project_id = ? 
            ORDER BY ph.start_date ASC, ph.phase_id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $phases[] = $row;
        $totalPhases++;
        
        $status = strtolower($row['status'] ?? '');
        if ($status === 'pending' || $status === 'not started') $pendingCount++;
        if ($status === 'in progress' || $status === 'active') $inProgressCount++;
        if ($status === 'completed') $completedCount++;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Phases | <?php echo htmlspecialchars($project['project_name'] ?? 'Unknown'); ?> | ICMIS</title>
    
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
        $pageTitle = "Project Phases";
        $pageSection = "Project Management";
        $pageSubTitle = '<span class="text-sm text-gray-500">' . htmlspecialchars($project['project_name'] ?? 'Unknown Project') . '</span>';
        include __DIR__ . '/../../includes/header.php'; 
    ?>

    <main class="ml-56 mt-16 p-6">
        <?php if (!$project): ?>
        <!-- Invalid Project -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12">
            <div class="max-w-md mx-auto text-center">
                <div class="flex justify-center mb-6">
                    <div class="bg-red-50 rounded-full p-6">
                        <svg class="w-16 h-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
                <h2 class="text-xl text-gray-900 font-bold mb-3">Project Not Found</h2>
                <p class="text-gray-500 mb-8">The project you're looking for doesn't exist or has been deleted.</p>
                <a href="projects.php" class="inline-flex items-center gap-2 bg-[#e9922c] text-white px-6 py-3 rounded-lg hover:bg-[#d17f1f] transition-all duration-200 shadow-md font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Projects
                </a>
            </div>
        </div>
        <?php else: ?>
        
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm mb-6">
            <a href="projects.php" class="text-gray-500 hover:text-[#e9922c] transition-colors">Projects</a>
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($project['project_name']); ?></span>
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-gray-900 font-medium">Phases</span>
        </nav>

        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl text-gray-900 font-bold">Project Phases</h1>
                <p class="text-sm text-gray-500 mt-1">Manage phases for: <?php echo htmlspecialchars($project['project_name']); ?></p>
            </div>
            <div class="flex items-center gap-3">
                <a href="projects.php" class="flex items-center gap-2 bg-white text-gray-700 px-4 py-2.5 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span class="font-medium">Back</span>
                </a>
                <button id="addPhaseBtn" class="flex items-center gap-2 bg-[#e9922c] text-white px-6 py-2.5 rounded-lg hover:bg-[#d17f1f] transition-colors duration-200 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="font-medium">Add Phase</span>
                </button>
            </div>
        </div>

        <!-- Stat Cards -->
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
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo $totalPhases; ?></h2>
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
                        <p class="text-sm text-gray-500">Pending</p>
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo $pendingCount; ?></h2>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Completed</p>
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo $completedCount; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($phases)): ?>
        <!-- Empty State -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12">
            <div class="max-w-md mx-auto text-center">
                <div class="flex justify-center mb-6">
                    <div class="bg-orange-50 rounded-full p-6">
                        <svg class="w-16 h-16 text-[#e9922c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </div>
                </div>
                <h2 class="text-xl text-gray-900 font-bold mb-3">No Phases Yet</h2>
                <p class="text-gray-500 mb-8 leading-relaxed">
                    Start by creating the first phase for this project. Break down your project into 
                    manageable phases to track progress effectively.
                </p>
                <button onclick="document.getElementById('addPhaseBtn').click()" class="inline-flex items-center gap-2 bg-[#e9922c] text-white px-6 py-3 rounded-lg hover:bg-[#d17f1f] transition-all duration-200 shadow-md font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create First Phase
                </button>
            </div>
        </div>
        <?php else: ?>
        <!-- Phases Table -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full" id="phasesTable">
                    <thead>
                        <tr class="bg-gradient-to-r from-slate-800 to-slate-700">
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Phase</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Description</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Timeline</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Duration</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Tasks</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-white uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($phases as $index => $phase): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="bg-slate-100 rounded-lg p-2 w-10 h-10 flex items-center justify-center">
                                        <span class="text-sm font-bold text-slate-600"><?php echo $index + 1; ?></span>
                                    </div>
                                    <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($phase['phase_name']); ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-600 max-w-xs truncate"><?php echo htmlspecialchars($phase['description'] ?? '-'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo !empty($phase['start_date']) ? date('M d, Y', strtotime($phase['start_date'])) : '-'; ?></div>
                                <?php if (!empty($phase['end_date'])): ?>
                                <div class="text-xs text-gray-500">to <?php echo date('M d, Y', strtotime($phase['end_date'])); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600"><?php echo htmlspecialchars($phase['duration'] ?? '-'); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="tasks.php?project_id=<?php echo $projectId; ?>&phase_id=<?php echo $phase['phase_id']; ?>" 
                                   class="inline-flex items-center gap-1 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-sm font-medium hover:bg-blue-100 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                    <?php echo $phase['task_count']; ?> Tasks
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                    $statusClass = match(strtolower($phase['status'] ?? '')) {
                                        'pending', 'not started' => 'bg-yellow-100 text-yellow-700',
                                        'in progress', 'active' => 'bg-green-100 text-green-700',
                                        'completed' => 'bg-purple-100 text-purple-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                        default => 'bg-gray-100 text-gray-700'
                                    };
                                ?>
                                <span class="px-3 py-1 text-xs font-bold rounded-full <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($phase['status'] ?? 'Unknown'); ?>
                                </span>
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
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </main>

    <!-- Phase Modal -->
    <?php include __DIR__ . '/components/phase_modal.php'; ?>

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
        const projectId = <?php echo $projectId; ?>;
        const backendUrl = "api/phases.php";
        let phaseToDelete = null;

        // ------------------ Add Phase ------------------
        document.getElementById('addPhaseBtn')?.addEventListener('click', function() {
            document.getElementById('phaseForm').reset();
            document.getElementById('phaseModalTitle').textContent = 'Add Phase';
            document.getElementById('phaseModalBtnText').textContent = 'Add Phase';
            document.getElementById('phase_id').value = '';
            document.getElementById('phase_project_id').value = projectId;
            document.getElementById('phaseModal').style.display = 'flex';
        });

        // ------------------ Edit Phase ------------------
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const phaseId = this.dataset.id;

                fetch(backendUrl + '?fetch_id=' + phaseId)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const phase = data.phase;
                            document.getElementById('phase_id').value = phase.phase_id;
                            document.getElementById('phase_project_id').value = phase.project_id;
                            document.getElementById('phase_name').value = phase.phase_name;
                            document.getElementById('phase_description').value = phase.description || '';
                            document.getElementById('phase_start_date').value = phase.start_date || '';
                            document.getElementById('phase_end_date').value = phase.end_date || '';
                            document.getElementById('phase_duration').value = phase.duration || '';
                            document.getElementById('phase_status').value = phase.status || 'Pending';
                            
                            document.getElementById('phaseModalTitle').textContent = 'Edit Phase';
                            document.getElementById('phaseModalBtnText').textContent = 'Save Changes';
                            document.getElementById('phaseModal').style.display = 'flex';
                        } else {
                            showToast(data.message || 'Error fetching phase', 'error');
                        }
                    });
            });
        });

        // ------------------ Delete Phase ------------------
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                phaseToDelete = this.dataset.id;
                document.getElementById('deletePhaseName').textContent = this.dataset.name;
                document.getElementById('deleteModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            });
        });

        function closeDeleteModal() {
            phaseToDelete = null;
            document.getElementById('deleteModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function confirmDelete() {
            if (!phaseToDelete) return;

            const btn = document.getElementById('confirmDeleteBtn');
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Deleting...`;

            const formData = new FormData();
            formData.append('delete_id', phaseToDelete);

            fetch(backendUrl, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Phase deleted successfully', 'success', true);
                        closeDeleteModal();
                        setTimeout(() => window.location.reload(), 300);
                    } else {
                        showToast(data.message || 'Error deleting phase', 'error');
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                    }
                })
                .catch(err => {
                    showToast('Error deleting phase: ' + err.message, 'error');
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

        // ------------------ Close Phase Modal ------------------
        function closePhaseModal() {
            document.getElementById('phaseForm').reset();
            document.getElementById('phaseModal').style.display = 'none';
        }
        document.getElementById('closePhaseModal')?.addEventListener('click', closePhaseModal);
        document.getElementById('cancelPhaseModal')?.addEventListener('click', closePhaseModal);

        // ------------------ Submit Add/Edit ------------------
        document.getElementById('phaseForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);

            fetch(backendUrl, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Phase saved successfully', 'success', true);
                        closePhaseModal();
                        setTimeout(() => window.location.reload(), 300);
                    } else {
                        showToast(data.message || 'Error saving phase', 'error');
                    }
                })
                .catch(err => {
                    showToast('Error: ' + err.message, 'error');
                });
        });
    </script>
</body>
</html>
