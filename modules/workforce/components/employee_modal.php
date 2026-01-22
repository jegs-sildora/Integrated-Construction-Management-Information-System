<style>
/* Modal open/close animations */
.modal-content{
    transform: translateY(-10px) scale(0.98);
    opacity: 0;
    transition: transform 240ms cubic-bezier(.4,0,.2,1), opacity 240ms cubic-bezier(.4,0,.2,1);
}
.modal-open{
    transform: translateY(0) scale(1);
    opacity: 1;
}
.modal-close{
    transform: translateY(-10px) scale(0.98);
    opacity: 0;
}
/* Custom Scrollbar for the modal body */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f1f1; 
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #d1d5db; 
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #9ca3af; 
}
</style>

<?php if(!isset($job_titles)) { include __DIR__ . '/../api/job_titles_include.php'; } ?>

<div id="employeeModal" class="hidden fixed inset-0 z-50 overflow-hidden">
    <div class="flex items-center justify-center min-h-screen px-4 py-4">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
        
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full z-10 overflow-hidden transform transition-all modal-content flex flex-col max-h-[90vh]">
            
            <div class="bg-[#d17f1f] px-6 py-5 shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center shadow-inner border border-white/10">
                            <i data-lucide="user-plus" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <h3 id="modalTitle" class="text-xl font-bold text-white tracking-wide">Add New Employee</h3>
                            <p class="text-sm text-blue-50/90 font-medium">Create and manage workforce profiles</p>
                        </div>
                    </div>
                    <button onclick="closeModal()" class="group p-2 hover:bg-white/20 rounded-xl transition-all duration-200">
                        <i data-lucide="x" class="w-6 h-6 text-white"></i>
                    </button>
                </div>
            </div>

            <form id="employeeForm" class="flex flex-col flex-1 overflow-hidden bg-gray-50/50">
                <input type="hidden" id="form_action" name="action" value="create" autocomplete="off"> 
                <input type="hidden" id="employee_id" name="employee_id" autocomplete="off">
                
                <div class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-8">
                    
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-2">
                            <i data-lucide="user" class="w-5 h-5 text-[#d17f1f]"></i>
                            <h4 class="text-lg font-bold text-gray-800">Personal Information</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-5 mb-5">
                            <div class="md:col-span-4">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">First Name <span class="text-red-500">*</span></label>
                                <input type="text" id="first_name" name="first_name" required  autocomplete="off" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all font-medium text-gray-700 placeholder-gray-400" placeholder="e.g. Juan">
                            </div>
                            <div class="md:col-span-4">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" id="last_name" name="last_name" required autocomplete="off" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all font-medium text-gray-700 placeholder-gray-400" placeholder="e.g. Dela Cruz">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Suffix</label>
                                <input type="text" id="suffix" name="suffix" autocomplete="off" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all font-medium text-gray-700" placeholder="Jr.">
                            </div>
                             <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Gender</label>
                                <select id="gender" name="gender" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all font-medium text-gray-700">
                                    <option value="">Select</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
                            <div class="relative">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Birthday</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="calendar" class="w-4 h-4"></i></span>
                                    <input type="date" id="birthday" name="birthday" autocomplete="off" class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all font-medium text-gray-700">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Age</label>
                                <input type="number" id="age" name="age" readonly autocomplete="off" class="w-full px-4 py-3 bg-gray-100 border-transparent rounded-xl text-gray-500 font-bold text-center" placeholder="--">
                            </div>
                            <div class="relative">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Email Address</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="mail" class="w-4 h-4"></i></span>
                                    <input type="email" id="email" name="email" readonly autocomplete="off" class="w-full pl-10 pr-4 py-3 bg-gray-100 border-transparent rounded-xl text-gray-500 text-sm font-medium focus:ring-0" placeholder="Auto-generated...">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                             <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Phone Number</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="smartphone" class="w-4 h-4"></i></span>
                                    <input type="tel" id="phone" name="phone" value="+63 " autocomplete="off" maxlength="14" data-max-digits="10" class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all font-medium text-gray-700 tracking-wide">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Home Address</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-4 text-gray-400"><i data-lucide="map-pin" class="w-4 h-4"></i></span>
                                    <textarea id="address" name="address" rows="1" class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all font-medium text-gray-700 resize-none" placeholder="House #, Street, City"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-2">
                            <i data-lucide="briefcase" class="w-5 h-5 text-[#d17f1f]"></i>
                            <h4 class="text-lg font-bold text-gray-800">Role & Compensation</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                             <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Position / Job Title</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="hard-hat" class="w-4 h-4"></i></span>
                                    <input type="text" id="position" name="position" list="jobTitlesList" autocomplete="off" class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all font-bold text-gray-700" placeholder="Search title...">
                                </div>
                                <datalist id="jobTitlesList">
                                    <?php if(!empty($job_titles)): ?>
                                        <?php foreach($job_titles as $jt): ?>
                                            <option value="<?php echo htmlspecialchars($jt['title_name'], ENT_QUOTES); ?>" data-id="<?php echo (int)$jt['job_title_id']; ?>" data-department="<?php echo htmlspecialchars($jt['department'] ?? '', ENT_QUOTES); ?>" data-default-daily-rate="<?php echo htmlspecialchars($jt['default_daily_rate'] ?? '', ENT_QUOTES); ?>" data-default-monthly-salary="<?php echo htmlspecialchars($jt['default_monthly_salary'] ?? '', ENT_QUOTES); ?>"></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </datalist>
                            </div>
                             <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Department</label>
                                <div class="relative">
                                     <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="building-2" class="w-4 h-4"></i></span>
                                    <input type="text" id="department" name="department" readonly autocomplete="off" class="w-full pl-10 pr-4 py-3 bg-gray-100 border-transparent rounded-xl text-gray-600 font-medium" placeholder="Auto-filled">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Employment Type</label>
                                <select id="employment_type" name="employment_type" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all font-medium text-gray-700">
                                    <option value="Full-time">Full-time</option>
                                    <option value="Part-time">Part-time</option>
                                    <option value="Contract">Contract</option>
                                    <option value="Casual">Casual</option>
                                </select>
                            </div>
                             <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Payment Basis</label>
                                <select id="payment_type" name="payment_type" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all font-medium text-gray-700">
                                    <option value="Monthly">Monthly</option>
                                    <option value="Daily">Daily</option>
                                    <option value="Per Project">Per Project</option>
                                </select>
                            </div>
                             <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Current Status</label>
                                <select id="status" name="status" class="w-full px-4 py-3 bg-green-50 border border-green-200 text-green-700 font-bold rounded-xl focus:ring-2 focus:ring-green-500/20 outline-none">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Terminated">Terminated</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 p-4 bg-orange-50 rounded-xl border border-orange-100/50">
                            <div>
                                <label class="block text-xs font-bold text-orange-800 uppercase tracking-wider mb-2">Daily Rate (₱)</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-orange-400 font-bold">₱</span>
                                    <input type="text" id="daily_rate_display" inputmode="decimal" autocomplete="off" placeholder="0.00" class="w-full pl-10 pr-4 py-3 bg-white border border-orange-200 rounded-xl focus:ring-2 focus:ring-orange-300/40 focus:border-orange-400 outline-none transition-all font-bold text-gray-800 text-lg">
                                    <input type="hidden" id="daily_rate" name="daily_rate" autocomplete="off">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-orange-800 uppercase tracking-wider mb-2">Monthly Salary (₱)</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-orange-400 font-bold">₱</span>
                                    <input type="text" id="monthly_salary_display" inputmode="decimal" autocomplete="off" placeholder="0.00" class="w-full pl-10 pr-4 py-3 bg-white border border-orange-200 rounded-xl focus:ring-2 focus:ring-orange-300/40 focus:border-orange-400 outline-none transition-all font-bold text-gray-800 text-lg">
                                    <input type="hidden" id="monthly_salary" name="monthly_salary" autocomplete="off">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-2">
                             <i data-lucide="shield-check" class="w-5 h-5 text-[#d17f1f]"></i>
                            <h4 class="text-lg font-bold text-gray-800">Admin & Emergency</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                             <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Bank Name</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="landmark" class="w-4 h-4"></i></span>
                                    <input type="text" id="bank_name" name="bank_name" list="bankNamesList" autocomplete="off" class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all font-medium text-gray-700" placeholder="Select or type bank name">
                                    <datalist id="bankNamesList">
                                        <option value="BDO Unibank"></option>
                                        <option value="Bank of the Philippine Islands (BPI)"></option>
                                        <option value="Metropolitan Bank and Trust Company (Metrobank)"></option>
                                        <option value="Philippine National Bank (PNB)"></option>
                                        <option value="Land Bank of the Philippines (LANDBANK)"></option>
                                        <option value="Rizal Commercial Banking Corporation (RCBC)"></option>
                                        <option value="Security Bank Corporation"></option>
                                        <option value="Union Bank of the Philippines (UnionBank)"></option>
                                        <option value="China Banking Corporation (China Bank)"></option>
                                        <option value="EastWest Bank"></option>
                                        <option value="Development Bank of the Philippines (DBP)"></option>
                                        <option value="Asia United Bank (AUB)"></option>
                                        <option value="PSBank (The Philippine Savings Bank)"></option>
                                        <option value="Robinsons Bank"></option>
                                    </datalist>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Bank Account No.</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="credit-card" class="w-4 h-4"></i></span>
                                    <input type="tel" id="bank_account" name="bank_account" autocomplete="off" class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all font-medium text-gray-700 font-mono" placeholder="0000 0000 0000">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Emergency Contact</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="heart-pulse" class="w-4 h-4"></i></span>
                                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" autocomplete="off" class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all font-medium text-gray-700" placeholder="Name of relative">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Emergency Phone</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="phone-call" class="w-4 h-4"></i></span>
                                    <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" autocomplete="off" value="+63 " maxlength="14" data-max-digits="10" class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all font-medium text-gray-700">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Date Hired</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="calendar-check" class="w-4 h-4"></i></span>
                                    <input type="date" id="hire_date" name="hire_date" autocomplete="off" value="<?php echo date('Y-m-d'); ?>" class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all font-medium text-gray-700">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Admin Notes</label>
                                <input type="text" id="notes" name="notes" autocomplete="off" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c]/20 focus:border-[#e9922c] outline-none transition-all font-medium text-gray-700" placeholder="Internal remarks...">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white px-8 py-5 border-t border-gray-200 flex items-center justify-between shrink-0">
                    <div class="text-xs text-gray-400 font-medium">
                        <span class="text-red-500">*</span> Required fields
                    </div>
                    <div class="flex gap-4">
                        <button type="button" onclick="closeModal()" 
                            class="px-6 py-3 text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 hover:border-gray-400 font-bold transition-all shadow-sm">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="px-8 py-3 bg-[#d17f1f] text-white rounded-xl font-bold transition-all flex items-center gap-2 transform active:scale-95">
                            Save Employee
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/employee_modal.js"></script>