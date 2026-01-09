// Initialize Lucide icons
lucide.createIcons();

// Modal functionality removed - now using add_expense.php page for adding expenses

// Filter functionality removed

// Close modals when clicking outside
document.getElementById('viewExpenseModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeViewExpenseModal();
  }
});

document.getElementById('deleteExpenseModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeDeleteExpenseModal();
  }
});

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    const viewModal = document.getElementById('viewExpenseModal');
    const deleteModal = document.getElementById('deleteExpenseModal');
    
    if (!viewModal.classList.contains('hidden')) {
      closeViewExpenseModal();
    }
    if (!deleteModal.classList.contains('hidden')) {
      closeDeleteExpenseModal();
    }
  }
});