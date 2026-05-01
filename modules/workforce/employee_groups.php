<?php
// ============================================================
// EMPLOYEE GROUPS MANAGEMENT (Refactored)
// ============================================================

include __DIR__ . '/project_context.php';
require_once __DIR__ . '/../../core/ApiHelper.php';
$conn = getWorkforceConnection();

// Fetch Employees for the Member Selection List (All Active)
$employees = [];
$res_employees = ApiHelper::get('workforce/employees?status=Active');
if ($res_employees['status'] === 200) {
    $employees = $res_employees['data']['data'] ?? $res_employees['data'];
}

// Fetch Leaders/Foremen ONLY
$leaders = [];
$res_leaders = ApiHelper::get('workforce/employees?job_title_id=11&status=Active');
if ($res_leaders['status'] === 200) {
    $leaders = $res_leaders['data']['data'] ?? $res_leaders['data'];
}

$pageSection = "Labor & Workforce";
$pageTitle = "Deployment Groups";
$userName = $_SESSION['user_name'] ?? "Admin";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Groups | ICMIS</title>
    
    <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        .member-list-container::-webkit-scrollbar { width: 6px; }
        .member-list-container::-webkit-scrollbar-track { background: #f1f1f1; }
        .member-list-container::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
        .member-list-container::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
        .modal-content { transform: scale(0.95); opacity: 0; transition: all 0.2s ease-out; }
        .modal-open.modal-content { transform: scale(1); opacity: 1; }

        /* Animation Keyframes */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Animation Classes */
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }
        .animate-modal-slide-in {
            animation: modalSlideIn 0.3s ease-out 0.1s forwards; /* 0.1s delay for better effect */
            opacity: 0; /* Start invisible */
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <?php 
        include __DIR__ . '/../../includes/sidebar.php';
        include __DIR__ . '/../../includes/toast.php';
        include __DIR__ . '/../../includes/header.php'; 
    ?>

    <main class="ml-56 mt-16 p-6 transition-all duration-300 animate-fade-in">
        <div class="max-w-7xl mx-auto">
            
            <div class="flex items-center gap-1 mb-6 border-b border-gray-200">
                <button onclick="window.location.href='employees.php'" id="tab-employees" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                    All Employees
                </button>
                <button onclick="window.location.href='employee_groups.php'" id="tab-groups" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-[#e9922c] text-[#e9922c] transition-colors">
                    Deployment Groups
                </button>
            </div>

            <div class="flex items-end justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Deployment Groups</h1>
                    <p class="text-sm text-gray-500 mt-1">Organize workforce teams for assignments.</p>
                </div>
                <button onclick="openGroupModal()" class="flex items-center gap-2 bg-[#e9922c] text-white px-6 py-2.5 rounded-lg hover:bg-[#d17f1f] transition-colors duration-200 shadow-sm font-bold">
                    <i data-lucide="users-round" class="w-4 h-4"></i>
                    Create Deployment Group
                </button>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                <div id="groupsLoading" class="flex flex-col items-center justify-center h-96 text-center">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-[#e9922c]"></div>
                    <p class="mt-4 text-gray-500 font-medium">Loading deployment groups...</p>
                </div>

                <div id="groupsEmpty" class="hidden flex flex-col items-center justify-center h-128 text-center p-8">
                    <div class="w-20 h-20 bg-orange-50 rounded-full flex items-center justify-center mb-6">
                        <i data-lucide="hard-hat" class="w-10 h-10 text-orange-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">No Deployment Groups Yet</h3>
                    <p class="text-gray-500 max-w-md mx-auto mb-8">Create groups to organize specialized teams (e.g., "Masonry Team A") for easy assignment.</p>
                    <button onclick="openGroupModal()" class="inline-flex items-center gap-2 bg-white border border-gray-300 text-gray-700 px-5 py-2.5 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Create First Group
                    </button>
                </div>

                <div id="groupsTable" class="hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Group Code</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Group Name</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Leader</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Members</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="groupsTableBody" class="divide-y divide-gray-100 bg-white">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="groupModal" class="hidden fixed inset-0 z-50 overflow-hidden">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeGroupModal()"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full flex flex-col max-h-[90vh] modal-content">
                <div class="bg-gradient-to-r from-gray-900 to-gray-800 px-6 py-5 rounded-t-2xl shrink-0 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="bg-white/10 p-2 rounded-lg"><i data-lucide="users" class="w-5 h-5 text-white"></i></div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Deployment Group</h3>
                            <p class="text-xs text-gray-400">Define team composition</p>
                        </div>
                    </div>
                    <button onclick="closeGroupModal()" class="text-gray-400 hover:text-white transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>

                <form id="groupForm" onsubmit="event.preventDefault(); saveGroup();" class="flex flex-col flex-1 overflow-hidden">
                    <input type="hidden" name="action" id="group_action" value="create">
                    <input type="hidden" name="group_id" id="group_id" value="">
                    
                    <div class="flex-1 overflow-hidden custom-scrollbar p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Group Name</label>
                                    <input type="text" name="group_name" required placeholder="e.g. Masonry Team A" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c] focus:border-transparent outline-none transition-all font-medium">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Group Leader / Foreman</label>
                                    <select name="leader_id" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#e9922c] focus:border-transparent outline-none">
                                        <option value="">Select Leader...</option>
                                        <?php foreach($leaders as $leader): ?>
                                            <option value="<?php echo $leader['employee_id']; ?>"><?php echo htmlspecialchars($leader['first_name'] . ' ' . $leader['last_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Description</label>
                                    <textarea name="description" rows="7" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c] focus:border-transparent outline-none resize-none" placeholder="Purpose of this group..."></textarea>
                                </div>
                            </div>

                            <div class="flex flex-col h-[390px] member-list-container bg-gray-50 rounded-xl border border-gray-200 overflow-hidden">
                                <div class="px-4 py-3 border-b border-gray-200 bg-white flex justify-between items-center">
                                    <h4 class="text-sm font-bold text-gray-800">Add Members</h4>
                                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-md border border-gray-200" id="selectedCount">0 Selected</span>
                                </div>
                                <div class="p-3 border-b border-gray-200 bg-white">
                                    <div class="relative">
                                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                                        <input type="text" id="memberSearch" placeholder="Search employees..." class="w-full pl-9 pr-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-gray-400">
                                    </div>
                                </div>
                                <div class="flex-1 overflow-y-auto p-2 member-list-container space-y-1" id="memberList">
                                    <?php foreach($employees as $e): ?>
                                    <label class="flex items-center gap-3 p-2 hover:bg-white rounded-lg cursor-pointer transition-colors border border-transparent hover:border-gray-100 group">
                                        <input type="checkbox" name="members[]" value="<?php echo $e['employee_id']; ?>" class="member-checkbox w-4 h-4 text-[#e9922c] border-gray-300 rounded focus:ring-[#e9922c]">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-700 group-hover:text-gray-900"><?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($e['title_name'] ?? 'No Position'); ?></p>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 shrink-0">
                        <button type="button" onclick="closeGroupModal()" class="px-5 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-100 transition-colors">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-[#e9922c] rounded-xl hover:bg-[#d17f1f] transition-colors shadow-sm flex items-center gap-2"><i data-lucide="check" class="w-4 h-4"></i> Save Group</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity animate-fade-in" onclick="closeDeleteModal()"></div>

    <div class="flex items-center justify-center min-h-screen px-4 pointer-events-none">
        
        <div class="relative w-full max-w-md pointer-events-auto bg-white rounded-2xl shadow-2xl transform transition-all animate-modal-slide-in">
            
            <div class="bg-gradient-to-r from-red-600 to-red-700 p-6 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="bg-white rounded-full p-3 shadow-lg shrink-0">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-white leading-none">Delete Group</h3>
                            <p class="text-red-100 text-sm mt-1.5 opacity-90">Permanent action</p>
                        </div>
                    </div>
                    <button onclick="closeDeleteModal()" class="text-white/80 hover:text-white hover:bg-white/10 p-2 rounded-lg transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-8">
                <div class="mb-6">
                    <p class="text-gray-800 text-lg font-semibold mb-2">
                        Are you sure you want to delete this group?
                    </p>
                    <p class="text-gray-500 text-sm leading-relaxed">
                        This action is permanent and cannot be undone. The group composition and assignments will be removed.
                    </p>
                </div>
                
                <div class="bg-red-50 border-2 border-red-100 rounded-xl p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="bg-red-100 rounded-full p-2 shrink-0">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-red-900 mb-1.5 uppercase tracking-wide">Selected Group</p>
                            <div class="flex items-center">
                                <p id="deleteGroupName" class="text-lg font-bold text-red-700 font-mono bg-white px-3 py-1.5 rounded-lg border border-red-200 inline-block truncate max-w-full">
                                    Loading...
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 p-6 bg-gray-50 border-t border-gray-200 rounded-b-2xl">
                <button onclick="closeDeleteModal()" class="px-6 py-2.5 text-gray-700 bg-white border-2 border-gray-300 rounded-xl hover:bg-gray-100 hover:border-gray-400 transition-all font-bold shadow-sm">
                    Cancel
                </button>
                <button id="confirmDeleteBtn" onclick="confirmDelete()" class="px-6 py-2.5 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-xl transition-all font-bold flex items-center gap-2 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete Group
                </button>
            </div>
        </div>
    </div>
</div>

    <script src="js/employee_groups.js"></script>
</body>
</html>