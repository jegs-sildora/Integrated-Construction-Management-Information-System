<!-- Project Modal (Add/Edit) -->
<div id="projectModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 items-center justify-center p-4" style="display: none;">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full transform transition-all animate-modal-slide-in max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-slate-800 to-slate-700 p-6 rounded-t-2xl shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="bg-[#e9922c] rounded-xl p-3 shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <h3 id="projectModalTitle" class="text-2xl font-bold text-white">Add Project</h3>
                        <p class="text-slate-300 text-sm mt-1">Enter project details</p>
                    </div>
                </div>
                <button id="closeProjectModal" type="button" class="text-slate-300 hover:text-white hover:bg-white/10 p-2 rounded-lg transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <form id="projectForm" class="flex-1 overflow-y-auto">
            <input type="hidden" name="project_id" id="project_id" value="">
            
            <div class="p-6 space-y-6">
                <!-- Basic Info Section -->
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#e9922c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Basic Information
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Project Code</label>
                            <input type="text" name="project_code" id="project_code" readonly
                                   class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-lg text-sm text-gray-600 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Project Name <span class="text-red-500">*</span></label>
                            <input type="text" name="project_name" id="project_name" required autocomplete="off"
                                   placeholder="Enter project name"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                        <textarea name="description" id="description" rows="3"
                                  placeholder="Enter project description"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all resize-none"></textarea>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Location</label>
                        <input type="text" name="location" id="location" autocomplete="off"
                               placeholder="Enter project location"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all">
                    </div>
                </div>

                <!-- Timeline & Status Section -->
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#e9922c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Timeline & Status
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Start Date</label>
                            <input type="date" name="start_date" id="start_date"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">End Date</label>
                            <input type="date" name="end_date" id="end_date"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                            <select name="status" id="status"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all">
                                <option value="Planning">Planning</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Active">Active</option>
                                <option value="On Hold">On Hold</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Project Manager</label>
                               <input list="managerList" id="managerInput" name="manager_display"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all"
                                   placeholder="Select Project Manager">
                            <datalist id="managerList"></datalist>
                               <input type="hidden" name="project_manager_id" id="project_manager_id_hidden" value="">
                               <!-- Compatibility: some existing scripts expect a select#managerSelect; keep a hidden select populated for them -->
                               <select id="managerSelect" name="_manager_select_fallback" style="display:none"></select>
                        </div>
                    </div>
                </div>

                <!-- Budget Section -->
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#e9922c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Budget Information
                    </h4>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Total Budget (₱)</label>
                        <!-- Visible formatted input (no name) -->
                        <input type="text" id="total_budget_display" placeholder="Enter total budget" autocomplete="off"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all">
                        <!-- Hidden raw value submitted to server -->
                        <input type="hidden" name="total_budget" id="total_budget" value="">
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 p-6 bg-gray-50 border-t border-gray-200 rounded-b-2xl shrink-0">
                <button type="button" id="cancelProjectModal"
                        class="px-6 py-3 text-gray-700 bg-white border-2 border-gray-300 rounded-xl hover:bg-gray-100 transition-all font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-[#e9922c] to-[#d17f1f] hover:from-[#d17f1f] hover:to-[#b86d0f] text-white rounded-xl transition-all font-semibold flex items-center gap-2 shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span id="projectModalBtnText">Add Project</span>
                </button>
            </div>
        </form>
    </div>
</div>
    <script>
        (function(){
            const display = document.getElementById('total_budget_display');
            const hidden = document.getElementById('total_budget');

            if (!display || !hidden) return;

            function cleanRaw(val){
                if (!val) return '';
                // remove commas and non-numeric except dot
                let raw = val.replace(/,/g, '').replace(/[^0-9.]/g, '');
                // allow only one dot
                const parts = raw.split('.');
                if (parts.length > 1) {
                    raw = parts[0] + '.' + parts.slice(1).join('').slice(0,2);
                }
                return raw;
            }

            function formatDisplayFromRaw(raw){
                if (!raw) return '';
                const parts = raw.split('.');
                const intPart = parts[0] || '';
                const decPart = parts[1] || '';
                const withCommas = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                return decPart ? withCommas + '.' + decPart : withCommas;
            }

            display.addEventListener('input', function(e){
                const raw = cleanRaw(display.value);
                hidden.value = raw;
                display.value = formatDisplayFromRaw(raw);
            });

            // When form is reset programmatically, keep display in sync
            const form = document.getElementById('projectForm');
            if (form) {
                form.addEventListener('reset', function(){
                    hidden.value = '';
                    display.value = '';
                    const mgrHidden = document.getElementById('project_manager_id_hidden');
                    const mgrInput = document.getElementById('managerInput');
                    if (mgrHidden) mgrHidden.value = '';
                    if (mgrInput) mgrInput.value = '';
                });
            }
        })();
    </script>
    <script>
        (function(){
            // Populate project manager datalist with employees having job_title_id = 1
            const managerList = document.getElementById('managerList');
            const managerInput = document.getElementById('managerInput');
            const managerHidden = document.getElementById('project_manager_id_hidden');
            const managerMap = {}; // value -> id

            async function fetchManagers(){
                try{
                    const res = await fetch('../workforce/api/employees.php?action=list&status=Active');
                    const json = await res.json();
                    if (!json.success) return;
                    const employees = json.data || [];
                    // filter by job_title_id == 1
                    const managers = employees.filter(e => parseInt(e.job_title_id) === 1);
                    managerList.innerHTML = '';
                    // also populate fallback select if present
                    const fallbackSelect = document.getElementById('managerSelect');
                    if (fallbackSelect) fallbackSelect.innerHTML = '<option value="">Select Project Manager</option>';
                    managers.forEach(e => {
                        const label = `${e.first_name} ${e.last_name} (${e.employee_code || ''})`;
                        const option = document.createElement('option');
                        option.value = label;
                        managerList.appendChild(option);
                        managerMap[label] = e.employee_id;
                        if (fallbackSelect) {
                            const opt = document.createElement('option');
                            opt.value = e.employee_id;
                            opt.textContent = `${e.first_name} ${e.last_name} (${e.employee_code || ''})`;
                            // do not select here; edit flow will handle selecting
                            fallbackSelect.appendChild(opt);
                        }
                    });
                    // If hidden has a value (edit state), set input display
                    if (managerHidden && managerHidden.value) {
                        // try to find corresponding name
                        const match = Object.keys(managerMap).find(k => managerMap[k] == managerHidden.value);
                        if (match) managerInput.value = match;
                    }
                }catch(err){
                    console.error('Failed to load managers', err);
                }
            }

            if (managerList && managerInput) {
                fetchManagers();

                managerInput.addEventListener('input', function(){
                    const val = managerInput.value;
                    if (managerMap[val]) {
                        managerHidden.value = managerMap[val];
                    } else {
                        managerHidden.value = '';
                    }
                });
            }
        })();
    </script>
