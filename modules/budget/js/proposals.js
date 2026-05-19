

// AJAX Toast Function
function showToastAjax(message, type = 'success', persist = false) {
    if (persist) {
        sessionStorage.setItem('pendingToast', JSON.stringify({ message, type }));
        return;
    }
    fetch('/includes/toast.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message, type })
    }).then(r => r.json()).then(data => {
        document.getElementById('toast-container').insertAdjacentHTML('beforeend', data.html);
        setTimeout(() => dismissToast(data.id), 4000);
    }).catch(err => console.error('Toast error:', err));
}

let proposalToDelete = null;

function openDeleteModal(proposalId, proposalCode) {
  proposalToDelete = proposalId;
  document.getElementById('deleteProposalCode').textContent = proposalCode;
  document.getElementById('deleteModal').classList.remove('hidden');
  // Prevent body scroll when modal is open
  document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
  proposalToDelete = null;
  document.getElementById('deleteModal').classList.add('hidden');
  // Restore body scroll
  document.body.style.overflow = '';
}

function confirmDelete() {
  if (!proposalToDelete) return;

  const confirmBtn = document.getElementById('confirmDeleteBtn');
  const originalBtnContent = confirmBtn.innerHTML;
  
  // Disable button and show loading state
  confirmBtn.disabled = true;
  confirmBtn.innerHTML = `
    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    Deleting...
  `;

  fetch(`${window.GATEWAY_URL}budget/proposals?id=${proposalToDelete}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${window.AUTH_TOKEN}`
    }
  })
  .then(response => response.json())
  .then(result => {
    if (result.success) {
      showToastAjax(result.message, 'success', true);
      closeDeleteModal();
      // Reload page after short delay
      setTimeout(() => {
        window.location.reload();
      }, 300);
    } else {
      showToastAjax('Error: ' + result.message, 'error');
      // Re-enable button
      confirmBtn.disabled = false;
      confirmBtn.innerHTML = originalBtnContent;
    }
  })
  .catch(error => {
    console.error('Delete error:', error);
    showToastAjax('Error deleting proposal: ' + error.message, 'error');
    // Re-enable button
    confirmBtn.disabled = false;
    confirmBtn.innerHTML = originalBtnContent;
  });
}

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeDeleteModal();
  }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    const deleteModal = document.getElementById('deleteModal');
    if (!deleteModal.classList.contains('hidden')) {
      closeDeleteModal();
    }
  }
});