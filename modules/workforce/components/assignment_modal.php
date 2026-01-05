<!-- Assignment Modal -->
<div id="assignmentModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeModal()"></div>
        
        <!-- Modal Content -->
        <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6 z-10">
            <div class="flex items-center justify-between mb-6">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Add New Assignment</h3>
                <button onclick="closeModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
                </button>
            </div>

            <form id="assignmentForm" onsubmit="event.preventDefault(); saveAssignment();">
                <input type="hidden" id="assignment_id" name="assignment_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Employee <span class="text-red-500">*</span></label>
                    <select id="employee_id" name="employee_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none">
                        <option value="">-- Select Employee --</option>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['employee_id']; ?>">
                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (#' . $emp['employee_code'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Project <span class="text-red-500">*</span></label>
                    <select id="project_id" name="project_id" required onchange="updatePhases(this.value)" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none">
                        <option value="">-- Select Project --</option>
                        <?php foreach ($projects as $proj): ?>
                        <option value="<?php echo $proj['project_id']; ?>">
                            <?php echo htmlspecialchars($proj['project_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phase</label>
                    <select id="phase_id" name="phase_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none">
                        <option value="">-- Select Phase --</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <input type="text" id="role" name="role" placeholder="e.g., Site Foreman, Lead Mason" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none">
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="assignment_status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] outline-none">
                        <option value="Active">Active</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-[#e9922c] text-white rounded-lg hover:bg-[#d17f1f] transition-colors">
                        Save Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
