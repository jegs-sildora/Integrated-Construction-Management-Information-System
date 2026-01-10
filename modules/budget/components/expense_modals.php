<!-- View Expense Details Modal removed -->
<!-- Edit Expense Modal -->
<div id="editExpenseModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fade-in">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden animate-zoom-in">
        
        <!-- Header Section with Dark Gradient -->
        <div class="bg-gradient-to-r from-slate-800 to-slate-700 px-6 py-5 relative">
            <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-[#e9922c] via-orange-300 to-[#e9922c]"></div>
            
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-[#e9922c] to-[#d17f1f] rounded-lg flex items-center justify-center text-white text-2xl font-bold transform shadow-lg">
                        I
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Edit Expense</h2>
                        <p class="text-gray-300 text-sm">Update expense information</p>
                    </div>
                </div>
                <button onclick="closeEditExpenseModal()" class="text-white hover:bg-white/10 rounded-lg p-2 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Scrollable Content Area -->
        <div class="overflow-y-auto max-h-[calc(90vh-180px)]">
            <form id="edit-expense-form" class="px-6 py-6">
                <input type="hidden" id="edit-expense-id" name="expense_id">
                <div class="space-y-5">
                    <!-- Expense Date -->
                    <div>
                        <label for="edit-expense-date" class="block text-sm font-medium text-gray-700 mb-2">
                            Expense Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="edit-expense-date" name="expense_date" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-transparent transition-colors duration-200"
                            max="<?php echo date('Y-m-d'); ?>">
                        <div id="error-edit-expense-date" class="hidden mt-2 flex items-center gap-2 text-sm text-red-600">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span class="error-message"></span>
                        </div>
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="edit-category" class="block text-sm font-medium text-gray-700 mb-2">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select id="edit-category" name="category" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-transparent transition-colors duration-200">
                            <option value="" disabled>Select a category</option>
                            <option value="MATERIALS">Materials</option>
                            <option value="LABOR">Labor</option>
                            <option value="EQUIPMENT">Equipment</option>
                        </select>
                        <div id="error-edit-category" class="hidden mt-2 flex items-center gap-2 text-sm text-red-600">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span class="error-message"></span>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="edit-description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <textarea id="edit-description" name="description" rows="3" required maxlength="500"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-transparent transition-colors duration-200 resize-none"
                                placeholder="Enter detailed description of the expense..."></textarea>
                            <div class="absolute bottom-2 right-3 text-xs text-gray-400">
                                <span id="edit-char-count">0</span>/500
                            </div>
                        </div>
                        <div id="error-edit-description" class="hidden mt-2 flex items-center gap-2 text-sm text-red-600">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span class="error-message"></span>
                        </div>
                    </div>

                    <!-- Supplier/Vendor Name -->
                    <div>
                        <label for="edit-supplier-name" class="block text-sm font-medium text-gray-700 mb-2">
                            Supplier/Vendor Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="edit-supplier-name" name="supplier_name" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-transparent transition-colors duration-200"
                            placeholder="Enter supplier or vendor name">
                        <div id="error-edit-supplier-name" class="hidden mt-2 flex items-center gap-2 text-sm text-red-600">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span class="error-message"></span>
                        </div>
                    </div>

                    <!-- Amount -->
                    <div>
                        <label for="edit-amount" class="block text-sm font-medium text-gray-700 mb-2">
                            Amount (₱) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="edit-amount" name="amount" required step="0.01" min="0.01"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-transparent transition-colors duration-200"
                            placeholder="0.00">
                        <div id="error-edit-amount" class="hidden mt-2 flex items-center gap-2 text-sm text-red-600">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span class="error-message"></span>
                        </div>
                    </div>

                    <!-- Approval Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Approval Status <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-6">
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" id="edit-status-pending" name="status" value="PENDING"
                                    class="w-4 h-4 text-[#e9922c] border-gray-300">
                                <span class="ml-2 text-sm text-gray-700">Pending</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" id="edit-status-approved" name="status" value="APPROVED"
                                    class="w-4 h-4 text-[#e9922c] border-gray-300">
                                <span class="ml-2 text-sm text-gray-700">Approved</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" id="edit-status-rejected" name="status" value="REJECTED"
                                    class="w-4 h-4 text-[#e9922c] border-gray-300">
                                <span class="ml-2 text-sm text-gray-700">Rejected</span>
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
            <button type="button" onclick="closeEditExpenseModal()" class="px-5 py-2.5 bg-white border-2 border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-sm font-medium transition-colors duration-150">
                Cancel
            </button>
            <button type="submit" form="edit-expense-form" id="update-expense-btn" class="flex items-center gap-2 px-5 py-2.5 bg-[#e9922c] hover:bg-[#d17f1f] text-white rounded-lg text-sm font-medium transition-all duration-150 shadow-sm">
                <svg id="update-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <svg id="update-loading-icon" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span id="update-text">Update Expense</span>
            </button>
        </div>

        <div class="h-0.5 bg-gradient-to-r from-[#e9922c] via-orange-300 to-[#e9922c]"></div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteExpenseModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fade-in">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-zoom-in">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-5 relative">
            <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-red-400 via-red-300 to-red-400"></div>
            
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-red-600 shadow-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-white">Delete Expense</h2>
                    <p class="text-red-100 text-sm">This action cannot be undone</p>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <p class="text-gray-700 mb-4">Are you sure you want to delete this expense?</p>
            <div class="bg-red-50 border-2 border-red-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-gray-600 mb-1">Expense Description:</p>
                <p id="delete-expense-description" class="text-base font-bold text-gray-900">-</p>
            </div>
            <p class="text-sm text-red-600 font-semibold">⚠️ This will permanently remove the expense record from the system.</p>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-end gap-3">
            <button type="button" onclick="closeDeleteExpenseModal()" class="px-5 py-2.5 bg-white border-2 border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-sm font-medium transition-colors duration-150">
                Cancel
            </button>
            <button type="button" onclick="confirmDeleteExpense()" id="confirm-delete-btn" class="flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-all duration-150 shadow-sm">
                <svg id="delete-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <svg id="delete-loading-icon" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span id="delete-text">Delete Expense</span>
            </button>
        </div>

        <div class="h-0.5 bg-gradient-to-r from-red-400 via-red-300 to-red-400"></div>
    </div>
</div>

<script>
    let currentExpenseId = null;

    // View Expense Modal removed

    // Edit Expense Modal
    function openEditExpenseModal(expenseId) {
        currentExpenseId = expenseId;
        
        fetch(`budget_expenses/get_expense_details.php?id=${expenseId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const expense = data.expense;
                    
                    // Populate edit form
                    document.getElementById('edit-expense-id').value = expense.expense_id;
                    document.getElementById('edit-expense-date').value = expense.expense_date;
                    document.getElementById('edit-category').value = expense.category;
                    document.getElementById('edit-description').value = expense.description;
                    document.getElementById('edit-supplier-name').value = expense.supplier_name;
                    document.getElementById('edit-amount').value = expense.amount;
                    
                    // Update character count
                    document.getElementById('edit-char-count').textContent = expense.description.length;
                    
                    // Set status radio button
                    if (expense.status === 'APPROVED') {
                        document.getElementById('edit-status-approved').checked = true;
                    } else if (expense.status === 'REJECTED') {
                        document.getElementById('edit-status-rejected').checked = true;
                    } else {
                        document.getElementById('edit-status-pending').checked = true;
                    }
                    
                    document.getElementById('editExpenseModal').classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                } else {
                    showToast('Failed to load expense details: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error loading expense details', 'error');
            });
    }

    function closeEditExpenseModal() {
        document.getElementById('editExpenseModal').classList.add('hidden');
        document.body.style.overflow = '';
        document.getElementById('edit-expense-form').reset();
    }

    // Character counter for edit modal
    document.getElementById('edit-description')?.addEventListener('input', function() {
        document.getElementById('edit-char-count').textContent = this.value.length;
    });

    // Edit form submission
    document.getElementById('edit-expense-form')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const updateBtn = document.getElementById('update-expense-btn');
        const updateIcon = document.getElementById('update-icon');
        const updateLoadingIcon = document.getElementById('update-loading-icon');
        const updateText = document.getElementById('update-text');
        
        // Show loading state
        updateBtn.disabled = true;
        updateIcon.classList.add('hidden');
        updateLoadingIcon.classList.remove('hidden');
        updateText.textContent = 'Updating...';
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('budget_expenses/update_expense.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Persist toast so it appears after reload
                showToast('Expense updated successfully!', 'success', true);
                closeEditExpenseModal();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(result.message || 'Failed to update expense', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Error updating expense', 'error');
        } finally {
            updateBtn.disabled = false;
            updateIcon.classList.remove('hidden');
            updateLoadingIcon.classList.add('hidden');
            updateText.textContent = 'Update Expense';
        }
    });

    // Delete Expense Modal
    function openDeleteModal(expenseId, description) {
        currentExpenseId = expenseId;
        document.getElementById('delete-expense-description').textContent = description;
        document.getElementById('deleteExpenseModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeDeleteExpenseModal() {
        document.getElementById('deleteExpenseModal').classList.add('hidden');
        document.body.style.overflow = '';
        currentExpenseId = null;
    }

    async function confirmDeleteExpense() {
        if (!currentExpenseId) return;
        
        const deleteBtn = document.getElementById('confirm-delete-btn');
        const deleteIcon = document.getElementById('delete-icon');
        const deleteLoadingIcon = document.getElementById('delete-loading-icon');
        const deleteText = document.getElementById('delete-text');
        
        // Show loading state
        deleteBtn.disabled = true;
        deleteIcon.classList.add('hidden');
        deleteLoadingIcon.classList.remove('hidden');
        deleteText.textContent = 'Deleting...';
        
        const formData = new FormData();
        formData.append('expense_id', currentExpenseId);
        
        try {
            const response = await fetch('budget_expenses/delete_expense.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast('Expense deleted successfully!', 'success');
                closeDeleteExpenseModal();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(result.message || 'Failed to delete expense', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Error deleting expense', 'error');
        } finally {
            deleteBtn.disabled = false;
            deleteIcon.classList.remove('hidden');
            deleteLoadingIcon.classList.add('hidden');
            deleteText.textContent = 'Delete Expense';
        }
    }

    // Close modals on escape key (view modal removed)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (!document.getElementById('editExpenseModal').classList.contains('hidden')) {
                closeEditExpenseModal();
            }
            if (!document.getElementById('deleteExpenseModal').classList.contains('hidden')) {
                closeDeleteExpenseModal();
            }
        }
    });

    // Close modals when clicking outside
    document.getElementById('editExpenseModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeEditExpenseModal();
    });
    
    document.getElementById('deleteExpenseModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeDeleteExpenseModal();
    });

    function formatPeso(amount) {
        return amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
</script>
