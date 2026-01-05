<!-- Assignment Modal (Professional UI) -->
<div id="assignmentModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-8">
        <!-- Backdrop with Blur -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
        
        <!-- Modal Content -->
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full z-10 overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-[#e9922c] to-orange-500 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                            <i data-lucide="clipboard-list" class="w-5 h-5 text-white"></i>
                        </div>
                        <div>
                            <h3 id="modalTitle" class="text-lg font-bold text-white">Add New Assignment</h3>
                            <p class="text-sm text-white/80">Assign employee to a project</p>
                        </div>
                    </div>
                    <button onclick="closeModal()" class="p-2 hover:bg-white/10 rounded-xl transition-colors">
                        <i data-lucide="x" class="w-5 h-5 text-white"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <form id="assignmentForm" onsubmit="event.preventDefault(); saveAssignment();">
                <input type="hidden" id="assignment_id" name="assignment_id">
                
                <div class="px-6 py-6 max-h-[60vh] overflow-y-auto">
                    <!-- Employee Selection -->
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Employee <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <i data-lucide="user" class="w-4 h-4"></i>
                            </span>
                            <select id="employee_id" name="employee_id" required 
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all bg-white appearance-none">
                                <option value="">-- Select Employee --</option>
                                <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo $emp['employee_id']; ?>">
                                    <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (#' . $emp['employee_code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                <i data-lucide="chevron-down" class="w-4 h-4"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Project Selection -->
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Project <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <i data-lucide="building-2" class="w-4 h-4"></i>
                            </span>
                            <select id="project_id" name="project_id" required onchange="updatePhases(this.value)" 
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all bg-white appearance-none">
                                <option value="">-- Select Project --</option>
                                <?php foreach ($projects as $proj): ?>
                                <option value="<?php echo $proj['project_id']; ?>">
                                    <?php echo htmlspecialchars($proj['project_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                <i data-lucide="chevron-down" class="w-4 h-4"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Phase Selection -->
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Phase</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <i data-lucide="git-branch" class="w-4 h-4"></i>
                            </span>
                            <select id="phase_id" name="phase_id" 
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all bg-white appearance-none">
                                <option value="">-- Select Phase --</option>
                            </select>
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                <i data-lucide="chevron-down" class="w-4 h-4"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Role -->
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Role</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <i data-lucide="briefcase" class="w-4 h-4"></i>
                            </span>
                            <input type="text" id="role" name="role" 
                                placeholder="e.g., Site Foreman, Lead Mason" 
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all">
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="grid grid-cols-2 gap-4 mb-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Start Date</label>
                            <input type="date" id="start_date" name="start_date" 
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">End Date</label>
                            <input type="date" id="end_date" name="end_date" 
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all">
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                        <div class="flex gap-3">
                            <label class="flex-1">
                                <input type="radio" name="status" value="Active" checked class="peer hidden">
                                <div class="px-4 py-2.5 border-2 border-gray-200 rounded-xl text-center cursor-pointer transition-all peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:text-green-700 hover:border-gray-300">
                                    Active
                                </div>
                            </label>
                            <label class="flex-1">
                                <input type="radio" name="status" value="Completed" class="peer hidden">
                                <div class="px-4 py-2.5 border-2 border-gray-200 rounded-xl text-center cursor-pointer transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 hover:border-gray-300">
                                    Completed
                                </div>
                            </label>
                            <label class="flex-1">
                                <input type="radio" name="status" value="Cancelled" class="peer hidden">
                                <div class="px-4 py-2.5 border-2 border-gray-200 rounded-xl text-center cursor-pointer transition-all peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 hover:border-gray-300">
                                    Cancelled
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal()" 
                        class="px-5 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 font-medium transition-all">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="px-5 py-2.5 bg-[#e9922c] text-white rounded-xl hover:bg-[#d17f1f] font-medium transition-all shadow-lg shadow-orange-500/25 flex items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        Save Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
