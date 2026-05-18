function openDeleteModal(id, reference) {
    // Set the ID in the hidden input
    document.getElementById('delete_po_id').value = id;
    // Set the current project id (if present on page)
    var currentProj = document.getElementById('selected_project_id');
    if (currentProj) document.getElementById('delete_project_id').value = currentProj.value || '';
    
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