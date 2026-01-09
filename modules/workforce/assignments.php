<?php
// assignments.php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../../config/database.php';
include __DIR__ . '/project_context.php';

// Initial Data for Dropdowns (SSR for speed)
$employees = $conn->query("SELECT employee_id, first_name, last_name, employee_code FROM workforce_employees WHERE status = 'Active' ORDER BY last_name ASC");
$projects = $conn->query("SELECT project_id, project_name FROM icmis_projects ORDER BY project_id DESC");
$groups = $conn->query("SELECT group_id, group_name FROM workforce_employee_groups ORDER BY group_name ASC");

// Context
$selected_project_id = getProjectContext($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments | ICMIS</title>
    <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
    .modal-content { 
        transform: scale(0.95); 
        opacity: 0; 
        transition: all 0.2s ease-out; 
    }
    .modal-content.modal-open { 
        transform: scale(1); 
        opacity: 1; 
    }

    .tab-active { border-bottom: 2px solid #e9922c; color: #e9922c; font-weight: 600; }
    .tab-inactive { border-bottom: 2px solid transparent; color: #6b7280; }
    
    .employee-avatar {
        width: 36px; height: 36px; border-radius: 50%;
        background: linear-gradient(135deg, #e9922c, #f59e0b);
        display: flex; align-items: center; justify-content: center;
        color: white; font-weight: 600; font-size: 14px;
    }
</style>
</head>
<body class="bg-gray-50 text-gray-900">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <?php include __DIR__ . '/../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../includes/toast.php'; ?>

    <main class="ml-56 mt-20 p-6">
        <div class="max-w-7xl mx-auto">
            
            <div class="flex flex-row items-end justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Workforce Assignments</h1>
                    <p class="text-sm text-gray-500 mt-1">Deploy individual staff or entire crews to projects.</p>
                </div>
                <div class="flex items-center gap-3">
                    <select id="projectContextFilter" class="bg-white border border-gray-300 text-gray-700 text-sm rounded-lg p-2.5 shadow-sm focus:ring-[#e9922c] focus:border-[#e9922c]">
                        <option value="0">All Projects</option>
                        <?php foreach($projects as $p): ?>
                            <option value="<?= $p['project_id'] ?>" <?= $p['project_id'] == $selected_project_id ? 'selected' : '' ?>><?= $p['project_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button onclick="Assignments.openModal()" class="flex items-center gap-2 bg-[#e9922c] text-white px-5 py-2.5 rounded-lg hover:bg-[#d17f1f] transition-all shadow-sm font-bold text-sm">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        New Assignment
                    </button>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm relative">
                <div id="tableLoader" class="absolute inset-0 bg-white/80 z-10 flex items-center justify-center hidden">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#e9922c]"></div>
                </div>

                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase">Employee</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase">Project / Phase</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase">Role</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase">Timeline</th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="assignmentsTableBody" class="divide-y divide-gray-100">
                        </tbody>
                </table>
                
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-between" id="paginationControls">
                    <span class="text-sm text-gray-500">Showing page <span id="currentPage">1</span> of <span id="totalPages">1</span></span>
                    <div class="flex gap-2">
                        <button id="prevBtn" class="px-3 py-1 bg-white border border-gray-300 rounded text-sm disabled:opacity-50">Prev</button>
                        <button id="nextBtn" class="px-3 py-1 bg-white border border-gray-300 rounded text-sm disabled:opacity-50">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="assignmentModal" class="hidden fixed inset-0 z-50 overflow-hidden">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="Assignments.closeModal()"></div>
            
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full flex flex-col max-h-[90vh] modal-content">
                
                <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-2xl">
                    <h3 class="text-lg font-bold text-gray-900" id="modalTitle">New Assignment</h3>
                    <button onclick="Assignments.closeModal()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>

                <div id="modeTabs" class="flex border-b border-gray-200">
                    <button onclick="Assignments.switchMode('individual')" id="tabIndividual" class="flex-1 py-3 text-sm font-medium text-center tab-active hover:bg-gray-50">
                        <i data-lucide="user" class="w-4 h-4 inline mr-1"></i> Individual
                    </button>
                    <button onclick="Assignments.switchMode('group')" id="tabGroup" class="flex-1 py-3 text-sm font-medium text-center tab-inactive hover:bg-gray-50">
                        <i data-lucide="users" class="w-4 h-4 inline mr-1"></i> Crew / Group
                    </button>
                </div>

                <form id="assignmentForm" onsubmit="event.preventDefault(); Assignments.save();" class="p-6 space-y-4 overflow-y-auto">
                    <input type="hidden" name="assignment_id" id="assignment_id">
                    <input type="hidden" name="mode" id="modeInput" value="individual">

                    <div id="fieldIndividual">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Employee</label>
                        <select name="employee_id" id="employee_id" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#e9922c] outline-none text-sm">
                            <option value="">Select Employee...</option>
                            <?php foreach($employees as $e): ?>
                                <option value="<?= $e['employee_id'] ?>"><?= $e['first_name'].' '.$e['last_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="fieldGroup" class="hidden">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Select Crew / Group</label>
                        <select name="group_id" id="group_id" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#e9922c] outline-none text-sm">
                            <option value="">Select Group...</option>
                            <?php foreach($groups as $g): ?>
                                <option value="<?= $g['group_id'] ?>"><?= $g['group_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-blue-600 mt-1"><i data-lucide="info" class="w-3 h-3 inline"></i> This will create individual assignments for all members.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Project</label>
                            <select name="project_id" id="project_id" required onchange="Assignments.fetchPhases(this.value)" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#e9922c] outline-none text-sm">
                                <option value="">Select Project...</option>
                                <?php foreach($projects as $p): ?>
                                    <option value="<?= $p['project_id'] ?>"><?= $p['project_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Phase</label>
                            <select name="phase_id" id="phase_id" disabled class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#e9922c] outline-none text-sm disabled:text-gray-400">
                                <option value="">Select Project First</option>
                            </select>
                        </div>
                    </div>

                    <div id="roleField">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Role on Site</label>
                        <input type="text" name="role" id="role" placeholder="e.g. Site Engineer (Leave blank for default)" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl outline-none text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Start Date</label>
                            <input type="date" name="start_date" id="start_date" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl outline-none text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl outline-none text-sm">
                        </div>
                    </div>

                    <div id="statusField" class="hidden">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Status</label>
                        <select name="status" id="status" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl outline-none text-sm">
                            <option value="Active">Active</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>

                    <button type="submit" id="saveBtn" class="w-full px-4 py-3 bg-[#e9922c] text-white rounded-xl hover:bg-[#d17f1f] font-bold shadow-sm transition-colors">
                        Deploy Workforce
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="js/assignments.js"></script>
    <script>
        // Init
        document.addEventListener('DOMContentLoaded', () => {
            Assignments.init();
        });
    </script>
</body>
</html>