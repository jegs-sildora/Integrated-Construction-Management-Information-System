/* =========================================
   1. GLOBAL VARIABLES & INIT
   ========================================= */
let orderIdToDelete = null;

document.addEventListener("DOMContentLoaded", () => {
    // 1. CHECK FOR URL PARAMETERS
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');

    if (msg === 'updated') {
        showToast('Purchase Order updated successfully!', 'success');
        window.history.replaceState(null, null, window.location.pathname);
    } 
    else if (msg === 'created') {
        showToast('New Purchase Order created successfully!', 'success');
        window.history.replaceState(null, null, window.location.pathname);
    }

    // 2. Initialize handlers
    setupDeleteHandler();
});

/* =========================================
   2. DELETE MODAL LOGIC
   ========================================= */

function openDeleteModal(id, reference) {
    orderIdToDelete = id;
    
    const displayEl = document.getElementById('delete_id_display');
    if (displayEl) displayEl.textContent = reference;

    const inputEl = document.getElementById('delete_po_id');
    if (inputEl) inputEl.value = id;

    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeDeleteModal() {
    orderIdToDelete = null;
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function setupDeleteHandler() {
    const deleteForm = document.getElementById('deleteForm');
    
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault(); 
            
            // FIX: Get ID directly from input to avoid variable conflicts
            const inputEl = document.getElementById('delete_po_id');
            const idToDelete = inputEl && inputEl.value ? inputEl.value : orderIdToDelete;

            if (!idToDelete) {
                showToast("Error: No Order ID found to delete.", "error");
                return;
            }

            const btn = deleteForm.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Deleting...`;

            const formData = new FormData();
            formData.append('po_id', idToDelete);

            fetch('php/delete_order.php', { 
                method: 'POST', 
                body: formData 
            }) 
            .then(res => {
                // Check if response is valid JSON
                const contentType = res.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return res.json();
                } else {
                    // If not JSON, it's likely a PHP Fatal Error (DB connection, etc.)
                    return res.text().then(text => { throw new Error(text || "Invalid Server Response"); });
                }
            })
            .then(data => {
                if(data.success) {
                    showToast("Order deleted successfully", "success");
                    closeDeleteModal();
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    showToast(data.message || "Failed to delete order", "error");
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(err => {
                console.error("Delete Error:", err);
                // Clean up error message if it's a long HTML string
                let msg = err.message.length > 50 ? "Server connection error (Check console)" : err.message;
                showToast(msg, "error");
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
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) existingToast.remove(); 

    const toast = document.createElement('div');
    toast.className = `toast-notification fixed bottom-5 right-5 px-6 py-4 rounded-xl shadow-2xl text-white font-bold transform transition-all duration-300 translate-y-20 opacity-0 z-50 flex items-center gap-3`;
    
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

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-20', 'opacity-0');
    });

    setTimeout(() => {
        toast.classList.add('translate-y-20', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}