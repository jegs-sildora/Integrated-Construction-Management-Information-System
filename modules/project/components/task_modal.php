<!-- Task Modal (Add/Edit) -->
<div id="taskModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 items-center justify-center p-4" style="display: none;">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full transform transition-all animate-modal-slide-in max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-slate-800 to-slate-700 p-6 rounded-t-2xl shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="bg-[#e9922c] rounded-xl p-3 shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <div>
                        <h3 id="taskModalTitle" class="text-2xl font-bold text-white">Add Task</h3>
                        <p class="text-slate-300 text-sm mt-1">Enter task details</p>
                    </div>
        <script>
            document.addEventListener('DOMContentLoaded', function(){ (function(){
                const list = document.getElementById('taskAssigneeList');
                const input = document.getElementById('taskAssigneeInput');
                const hidden = document.getElementById('assigned_to_employee_id_hidden');
                const fallback = document.getElementById('task_assignee');
                const map = {}; // label -> id

                async function fetchEmployees(){
                    try{
                        const res = await fetch('../workforce/api/employees.php?action=list&status=Active');
                        let json;
                        if (typeof parseJSONResponse === 'function') {
                            json = await parseJSONResponse(res);
                        } else {
                            try { json = await res.json(); } catch(e) { console.error('Invalid JSON for employees', e); return; }
                        }
                        if (!json || !json.success) return;
                        const employees = json.data || [];
                        if (list) list.innerHTML = '';
                        if (fallback) fallback.innerHTML = '<option value="">Unassigned</option>';
                        employees.forEach(e => {
                            const label = `${e.first_name} ${e.last_name} (${e.employee_code || ''}) | ${e.position || ''}`;
                            if (list) {
                                const opt = document.createElement('option');
                                opt.value = label;
                                list.appendChild(opt);
                            }
                            map[label] = e.employee_id;
                            if (fallback) {
                                const opt2 = document.createElement('option');
                                opt2.value = e.employee_id;
                                opt2.textContent = `${e.first_name} ${e.last_name} (${e.employee_code || ''})`;
                                fallback.appendChild(opt2);
                            }
                        });

                        // If hidden has value (edit), set input display
                        if (hidden && hidden.value) {
                            const match = Object.keys(map).find(k => map[k] == hidden.value);
                            if (match && input) input.value = match;
                        }
                    }catch(err){
                        console.error('Failed to load employees for task assignee', err);
                    }
                }

                if (input && list) {
                    fetchEmployees();
                    input.addEventListener('input', function(){
                        const val = input.value;
                        if (map[val]) hidden.value = map[val]; else hidden.value = '';
                    });
                }

                const form = document.getElementById('taskForm');
                if (form) form.addEventListener('reset', function(){ if (hidden) hidden.value = ''; if (input) input.value = ''; });
            })(); });
        </script>
                </div>
                <button id="closeTaskModal" type="button" class="text-slate-300 hover:text-white hover:bg-white/10 p-2 rounded-lg transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <form id="taskForm" class="flex-1 overflow-y-auto">
            <input type="hidden" name="task_id" id="task_id" value="">
            
            <div class="p-6 space-y-6">
                <!-- Task Info Section -->
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#e9922c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Task Information
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Project <span class="text-red-500">*</span></label>
                            <select name="project_id" id="task_project_id" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all">
                                <option value="">Select Project</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Phase</label>
                            <select name="phase_id" id="task_phase_id"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all">
                                <option value="">Select Phase (Optional)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Task Name <span class="text-red-500">*</span></label>
                        <input type="text" name="task_name" id="task_name" required autocomplete="off"
                               placeholder="Enter task name"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all">
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                        <textarea name="description" id="task_description" rows="3"
                                  placeholder="Enter task description"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all resize-none"></textarea>
                    </div>
                </div>

                <!-- Assignment Section -->
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#e9922c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Assignment
                    </h4>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Assign To</label>
                        <input list="taskAssigneeList" id="taskAssigneeInput" name="task_assignee_display"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all"
                               placeholder="Assign to employee (Full Name (code) | Job Title)">
                        <datalist id="taskAssigneeList"></datalist>
                        <!-- Hidden value submitted to server -->
                        <input type="hidden" name="assigned_to_employee_id" id="assigned_to_employee_id_hidden" value="">
                        <!-- Compatibility fallback: keep a hidden select for scripts expecting #task_assignee -->
                        <select id="task_assignee" name="_task_assignee_fallback" style="display:none"><option value="">Unassigned</option></select>
                    </div>
                </div>

                <!-- Timeline & Priority Section -->
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#e9922c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Timeline & Priority
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Start Date</label>
                            <input type="date" name="start_date" id="task_start_date"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Due Date</label>
                            <input type="date" name="due_date" id="task_due_date"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Priority</label>
                            <select name="priority" id="task_priority"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                            <select name="status" id="task_status"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all">
                                <option value="Pending">Pending</option>
                                <option value="Not Started">Not Started</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 p-6 bg-gray-50 border-t border-gray-200 rounded-b-2xl shrink-0">
                <button type="button" id="cancelTaskModal"
                        class="px-6 py-3 text-gray-700 bg-white border-2 border-gray-300 rounded-xl hover:bg-gray-100 transition-all font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-[#e9922c] to-[#d17f1f] hover:from-[#d17f1f] hover:to-[#b86d0f] text-white rounded-xl transition-all font-semibold flex items-center gap-2 shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span id="taskModalBtnText">Add Task</span>
                </button>
            </div>
        </form>
    </div>
</div>
