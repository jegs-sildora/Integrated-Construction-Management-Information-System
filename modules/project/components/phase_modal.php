<!-- Phase Modal (Add/Edit) -->
<div id="phaseModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 items-center justify-center p-4" style="display: none;">
    <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full transform transition-all animate-modal-slide-in max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-slate-800 to-slate-700 p-6 rounded-t-2xl shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="bg-[#e9922c] rounded-xl p-3 shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 id="phaseModalTitle" class="text-2xl font-bold text-white">Add Phase</h3>
                        <p class="text-slate-300 text-sm mt-1">Enter phase details</p>
                    </div>
                </div>
                <button id="closePhaseModal" type="button" class="text-slate-300 hover:text-white hover:bg-white/10 p-2 rounded-lg transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <form id="phaseForm" class="flex-1 overflow-y-auto">
            <input type="hidden" name="phase_id" id="phase_id" value="">
            <input type="hidden" name="project_id" id="phase_project_id" value="">
            
            <div class="p-6 space-y-6">
                <!-- Phase Info Section -->
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#e9922c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Phase Information
                    </h4>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Phase Name <span class="text-red-500">*</span></label>
                            <input type="text" name="phase_name" id="phase_name" required
                                   placeholder="e.g., Foundation, Framing, Roofing"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                            <textarea name="description" id="phase_description" rows="3"
                                      placeholder="Enter phase description"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all resize-none"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Timeline Section -->
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
                            <input type="date" name="start_date" id="phase_start_date"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">End Date</label>
                            <input type="date" name="end_date" id="phase_end_date"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Duration</label>
                            <input type="text" name="duration" id="phase_duration"
                                   placeholder="e.g., 2 weeks, 30 days"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                            <select name="status" id="phase_status"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] transition-all">
                                <option value="Pending">Pending</option>
                                <option value="Not Started">Not Started</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Active">Active</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 p-6 bg-gray-50 border-t border-gray-200 rounded-b-2xl shrink-0">
                <button type="button" id="cancelPhaseModal"
                        class="px-6 py-3 text-gray-700 bg-white border-2 border-gray-300 rounded-xl hover:bg-gray-100 transition-all font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-[#e9922c] to-[#d17f1f] hover:from-[#d17f1f] hover:to-[#b86d0f] text-white rounded-xl transition-all font-semibold flex items-center gap-2 shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span id="phaseModalBtnText">Add Phase</span>
                </button>
            </div>
        </form>
    </div>
</div>
