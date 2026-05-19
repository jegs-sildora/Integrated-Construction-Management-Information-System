/* =========================================
   1. GLOBAL VARIABLES & INIT
   ========================================= */

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

let orderIdToDelete = null;

document.addEventListener("DOMContentLoaded", () => {
    // 1. CHECK FOR URL PARAMETERS
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    if (msg === 'updated') {
        showToastAjax('Purchase Order updated successfully!', 'success');
        window.history.replaceState(null, null, window.location.pathname);
    } else if (msg === 'created') {
        showToastAjax('New Purchase Order created successfully!', 'success');
        window.history.replaceState(null, null, window.location.pathname);
    } else if (msg === 'deleted') {
        const ref = urlParams.get('ref') || '';
        const text = ref ? `Order ${ref} deleted successfully` : 'Order deleted successfully';
        showToastAjax(text, 'success');
        // Remove query params so refresh doesn't re-show or re-trigger anything
        window.history.replaceState(null, null, window.location.pathname);
        // Do not reload again; this ensures the toast shows once and no further reloads occur
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
        // If the form opts out of AJAX (data-no-ajax="1"), allow normal form submit/redirect
        if (deleteForm.dataset && deleteForm.dataset.noAjax && deleteForm.dataset.noAjax !== '0') {
            return; // do not attach AJAX submit handler
        }
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault(); 
            
            // FIX: Get ID directly from input to avoid variable conflicts
            const inputEl = document.getElementById('delete_po_id');
            const idToDelete = inputEl && inputEl.value ? inputEl.value : orderIdToDelete;

            if (!idToDelete) {
                showToastAjax("Error: No Order ID found to delete.", "error");
                return;
            }

            const btn = deleteForm.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Deleting...`;

            const url = (window.GATEWAY_URL || '/api/v1/') + 'procurement/orders';
            fetch(url, { 
                method: 'DELETE', 
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + (window.AUTH_TOKEN || '')
                },
                body: JSON.stringify({ po_id: idToDelete })
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
                if (data.success || data.status === 'success') {
                    const ref = data.po_reference || '';
                    // Redirect immediately to orders page with msg=deleted so toast displays after reload
                    const currentProj = document.getElementById('selected_project_id');
                    const params = new URLSearchParams();
                    if (currentProj && currentProj.value) params.set('project_id', currentProj.value);
                    params.set('msg', 'deleted');
                    if (ref) params.set('ref', ref);
                    closeDeleteModal();
                    window.location = window.location.pathname + '?' + params.toString();
                } else {
                    showToastAjax(data.message || 'Failed to delete order', 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(err => {
                console.error("Delete Error:", err);
                // Clean up error message if it's a long HTML string
                let msg = err.message.length > 50 ? "Server connection error (Check console)" : err.message;
                showToastAjax(msg, "error");
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