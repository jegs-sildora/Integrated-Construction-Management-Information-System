<!-- Employee Modal (Professional UI) -->
<div id="employeeModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
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
                            <i data-lucide="user-plus" class="w-5 h-5 text-white"></i>
                        </div>
                        <div>
                            <h3 id="modalTitle" class="text-lg font-bold text-white">Add New Employee</h3>
                            <p class="text-sm text-white/80">Fill in the employee details below</p>
                        </div>
                    </div>
                    <button onclick="closeModal()" class="p-2 hover:bg-white/10 rounded-xl transition-colors">
                        <i data-lucide="x" class="w-5 h-5 text-white"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <form id="employeeForm" onsubmit="event.preventDefault(); saveEmployee();">
                <input type="hidden" id="employee_id" name="employee_id">
                
                <div class="px-6 py-6 max-h-[60vh] overflow-y-auto">
                    <!-- Name Fields -->
                    <div class="grid grid-cols-2 gap-4 mb-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="first_name" name="first_name" required 
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all"
                                placeholder="John">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="last_name" name="last_name" required 
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all"
                                placeholder="Doe">
                        </div>
                    </div>

                    <!-- Employee Code -->
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Employee Code</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <i data-lucide="hash" class="w-4 h-4"></i>
                            </span>
                            <input type="text" id="employee_code" name="employee_code" 
                                placeholder="Auto-generated if empty" 
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all">
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <i data-lucide="mail" class="w-4 h-4"></i>
                            </span>
                            <input type="email" id="email" name="email" 
                                placeholder="john.doe@company.com"
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all">
                        </div>
                    </div>

                    <!-- Phone -->
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <i data-lucide="phone" class="w-4 h-4"></i>
                            </span>
                            <input type="tel" id="phone" name="phone" 
                                placeholder="+63 912 345 6789"
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all">
                        </div>
                    </div>

                    <!-- Status & Hire Date -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                            <select id="status" name="status" 
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all bg-white">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="On Leave">On Leave</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Hire Date</label>
                            <input type="date" id="hire_date" name="hire_date" 
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all">
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
                        Save Employee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
