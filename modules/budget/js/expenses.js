// Initialize Lucide icons
lucide.createIcons();

// Modal functionality removed - now using add_expense.php page for adding expenses

// Filter functionality removed

// Close modals when clicking outside (only delete modal remains)
const deleteModalEl = document.getElementById('deleteExpenseModal');
if (deleteModalEl) {
  deleteModalEl.addEventListener('click', function(e) {
    if (e.target === this) closeDeleteExpenseModal();
  });
}

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    const deleteModal = document.getElementById('deleteExpenseModal');
    if (deleteModal && !deleteModal.classList.contains('hidden')) closeDeleteExpenseModal();
  }
});

// Open the view expense modal and load details via AJAX
// View modal removed: related functions were removed