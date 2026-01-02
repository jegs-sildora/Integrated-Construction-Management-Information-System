/* =========================================
   1. GLOBAL VARIABLES & INIT
   ========================================= */
let orderIdToDelete = null;

document.addEventListener("DOMContentLoaded", () => {
    // 1. CHECK FOR URL PARAMETERS (From Redirects)
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');

    if (msg === 'updated') {
        showToast('Purchase Order updated successfully!', 'success');
        // Clean URL (remove ?msg=updated) without refreshing
        window.history.replaceState(null, null, window.location.pathname);
    } 
    else if (msg === 'created') {
        showToast('New Purchase Order created successfully!', 'success');
        window.history.replaceState(null, null, window.location.pathname);
    }

    // 2. Initialize existing handlers
    setupDeleteHandler();
});
/* =========================================
   2. DELETE MODAL LOGIC
   ========================================= */

// Called by the button in the PHP table loop
function openDeleteModal(id, reference) {
    orderIdToDelete = id;
    
    // Update the modal text
    const displayEl = document.getElementById('delete_id_display');
    if (displayEl) displayEl.textContent = reference;

    // Update the hidden input if it exists (for safety)
    const inputEl = document.getElementById('delete_po_id');
    if (inputEl) inputEl.value = id;

    // Show the modal
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.style.display = 'flex'; // Ensure flex layout for centering
    }
}

// Called by Cancel button or Backdrop click
function closeDeleteModal() {
    orderIdToDelete = null;
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
}

function setupDeleteHandler() {
    const deleteForm = document.getElementById('deleteForm');
    
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Stop standard form submission
            
            if (!orderIdToDelete) return;

            const btn = deleteForm.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            
            // UI Feedback
            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Deleting...`;

            // Prepare Data
            const formData = new FormData();
            formData.append('po_id', orderIdToDelete);

            // Send Request
            fetch('php/delete_order.php', { 
                method: 'POST', 
                body: formData 
            }) 
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    showToast("Order deleted successfully", "success");
                    closeDeleteModal();
                    // Reload the page to reflect changes since table is PHP-rendered
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(data.message || "Failed to delete order", "error");
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(err => {
                console.error("Delete Error:", err);
                showToast("Server connection error", "error");
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }
}

/* =========================================
   3. CLICK OUTSIDE TO CLOSE
   ========================================= */
window.onclick = function(event) {
    const deleteModal = document.getElementById('deleteModal');
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
}

/* =========================================
   4. TOAST NOTIFICATION HELPER
   ========================================= */
function showToast(message, type = 'success') {
    // Remove existing toasts
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) existingToast.remove();

    // Create container
    const toast = document.createElement('div');
    toast.className = `toast-notification fixed bottom-5 right-5 px-6 py-4 rounded-xl shadow-2xl text-white font-bold transform transition-all duration-300 translate-y-20 opacity-0 z-50 flex items-center gap-3`;
    
    // Style based on type
    if (type === 'success') {
        toast.classList.add('bg-green-600');
        toast.innerHTML = `<i class="fa-solid fa-circle-check"></i> <span>${message}</span>`;
    } else if (type === 'error') {
        toast.classList.add('bg-red-600');
        toast.innerHTML = `<i class="fa-solid fa-circle-xmark"></i> <span>${message}</span>`;
    } else {
        toast.classList.add('bg-blue-600');
        toast.innerHTML = `<i class="fa-solid fa-circle-info"></i> <span>${message}</span>`;
    }

    document.body.appendChild(toast);

    // Animate In
    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-20', 'opacity-0');
    });

    // Animate Out
    setTimeout(() => {
        toast.classList.add('translate-y-20', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}