<div id="deleteModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fade-in">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all animate-modal-slide-in">
        
        <div class="bg-gradient-to-r from-red-600 to-red-700 p-6 rounded-t-2xl flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="bg-white/20 p-2 rounded-lg">
                    <i class="fa-solid fa-triangle-exclamation text-white text-xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white">Delete Order</h3>
            </div>
            <button onclick="closeDeleteModal()" class="text-white/80 hover:text-white transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        
        <form id="deleteForm" method="POST" action="php/delete_order.php">
            <input type="hidden" name="po_id" id="delete_po_id">
            
            <div class="p-8">
                <div class="mb-6">
                    <p class="text-gray-700 text-lg font-medium mb-2">
                        Are you sure you want to delete this purchase order?
                    </p>
                    <p class="text-gray-500 text-sm">
                        This action is permanent and cannot be undone. All associated data will be removed.
                    </p>
                </div>
                
                <div class="bg-red-50 border-2 border-red-200 rounded-xl p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="bg-red-100 rounded-full p-2 shrink-0">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-red-900 mb-1.5 uppercase tracking-wide">Order Reference</p>
                            <p id="delete_id_display" class="text-lg font-bold text-red-700 font-mono bg-white px-3 py-2 rounded-lg border border-red-200">
                                PO-XXXX
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 p-6 bg-gray-50 border-t border-gray-200 rounded-b-2xl">
                <button type="button" onclick="closeDeleteModal()" class="px-6 py-3 text-gray-700 bg-white border-2 border-gray-300 rounded-xl hover:bg-gray-100 hover:border-gray-400 transition-all font-semibold shadow-sm cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-xl transition-all font-semibold flex items-center gap-2 shadow-lg hover:shadow-xl cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete Order
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openDeleteModal(id, reference) {
        // Set the ID in the hidden input
        document.getElementById('delete_po_id').value = id;
        
        // Set the display text (e.g., PO-2023-001)
        document.getElementById('delete_id_display').innerText = reference;
        
        // Show the modal
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        // Hide the modal
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // Close modal if clicking outside the content area
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !document.getElementById('deleteModal').classList.contains('hidden')) {
            closeDeleteModal();
        }
    });
</script>