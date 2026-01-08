// Moved from phases.php inline script
// Expects `backendUrl` and `projectsData` to be defined on the page (injected by PHP)

let phaseToDelete = null;

// Pagination
let currentPage = 1;
const itemsPerPage = 10;
let filteredRows = [];

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateTable();
});

// ------------------ Dashboard Refresh (AJAX) ------------------
// Fetches the current page HTML and updates Table & Stats in place.
async function refreshDashboard() {
    try {
        const response = await fetch(window.location.href);
        const text = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, 'text/html');

        // 1. Update Table Content
        const newTableContainer = doc.getElementById('phasesTableContainer');
        const currentTableContainer = document.getElementById('phasesTableContainer');
        if (newTableContainer && currentTableContainer) {
            currentTableContainer.innerHTML = newTableContainer.innerHTML;
        }

        // 2. Update Statistics
        const stats = ['statTotalPhases', 'statActivePhases', 'statCompletedPhases', 'statUpcomingPhases'];
        stats.forEach(id => {
            const newEl = doc.getElementById(id);
            const currentEl = document.getElementById(id);
            if (newEl && currentEl) {
                currentEl.textContent = newEl.textContent;
            }
        });

        // 3. Re-apply current filters and pagination
        updateTable();
        
    } catch (err) {
        console.error("Failed to refresh dashboard:", err);
        showToast("Dashboard updated, but UI refresh failed. Please reload manually.", "error");
    }
}

// ------------------ Search & Filter ------------------
document.getElementById('searchInput')?.addEventListener('input', updateTable);
document.getElementById('projectFilter')?.addEventListener('change', updateTable);

function updateTable() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const projectFilter = document.getElementById('projectFilter').value;
    const rows = document.querySelectorAll('.phase-row');
    
    filteredRows = [];
    rows.forEach(row => {
        const name = row.dataset.name || '';
        const projectName = row.dataset.projectName || '';
        const projectId = row.dataset.project;
        
        const matchesSearch = name.includes(searchTerm) || projectName.includes(searchTerm);
        const matchesProject = !projectFilter || projectId === projectFilter;
        
        if (matchesSearch && matchesProject) {
            filteredRows.push(row);
        }
    });
    
    // Reset to page 1 if current page is out of bounds (unless we are just refreshing)
    // For smoother UX on refresh, we could try to keep the page, but resetting to 1 is safer
    // to avoid empty pages if rows were deleted.
    // However, if we want to be smart:
    const maxPage = Math.ceil(filteredRows.length / itemsPerPage) || 1;
    if (currentPage > maxPage) currentPage = maxPage;
    
    renderPage();
}

function renderPage() {
    const rows = document.querySelectorAll('.phase-row');
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    
    // Hide all initially
    rows.forEach(row => row.style.display = 'none');
    
    // Show only filtered rows for current page
    filteredRows.forEach((row, index) => {
        if (index >= start && index < end) {
            row.style.display = '';
        }
    });
    
    // Update pagination info
    const total = filteredRows.length;
    const showStart = total > 0 ? start + 1 : 0;
    const showEnd = Math.min(end, total);
    const paginationInfo = document.getElementById('paginationInfo');
    if (paginationInfo) paginationInfo.textContent = `Showing ${showStart}-${showEnd} of ${total}`;
    
    // Update buttons
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    
    // Remove old listeners to prevent stacking (since we replaced HTML, buttons are new anyway, but safe practice)
    if(prevBtn) {
        const newPrev = prevBtn.cloneNode(true);
        prevBtn.parentNode.replaceChild(newPrev, prevBtn);
        newPrev.disabled = currentPage === 1;
        newPrev.addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage--;
                renderPage();
            }
        });
    }

    if(nextBtn) {
        const newNext = nextBtn.cloneNode(true);
        nextBtn.parentNode.replaceChild(newNext, nextBtn);
        newNext.disabled = end >= total;
        newNext.addEventListener('click', function() {
            const maxPage = Math.ceil(filteredRows.length / itemsPerPage);
            if (currentPage < maxPage) {
                currentPage++;
                renderPage();
            }
        });
    }
}

// ------------------ Event Delegation (Edit & Delete) ------------------
// Use delegation so buttons work after AJAX table refresh
document.addEventListener('click', function(e) {
    const editBtn = e.target.closest('.edit-btn');
    const deleteBtn = e.target.closest('.delete-btn');

    if (editBtn) {
        handleEditPhase(editBtn.dataset.id);
    } else if (deleteBtn) {
        phaseToDelete = deleteBtn.dataset.id;
        document.getElementById('deletePhaseName').textContent = deleteBtn.dataset.name;
        const modal = document.getElementById('deleteModal');
        if (modal) modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
});

// Populate project select in modal
function populateProjectSelect(selectedProjectId = null) {
    const select = document.getElementById('phase_project_id');
    if (!select) return;
    select.innerHTML = '<option value="">Select Project</option>';
    (projectsData || []).forEach(proj => {
        const selected = selectedProjectId == proj.project_id ? 'selected' : '';
        select.innerHTML += `<option value="${proj.project_id}" ${selected}>${proj.project_name}</option>`;
    });
}

// ------------------ Add Phase ------------------
document.getElementById('addPhaseBtn')?.addEventListener('click', function() {
    document.getElementById('phaseForm').reset();
    document.getElementById('phaseModalTitle').textContent = 'Add Phase';
    document.getElementById('phaseModalBtnText').textContent = 'Add Phase';
    document.getElementById('phase_id').value = '';
    populateProjectSelect();
    document.getElementById('phaseModal').style.display = 'flex';
});

// ------------------ Edit Phase Logic ------------------
function handleEditPhase(phaseId) {
    fetch(backendUrl + '?fetch_id=' + phaseId)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const phase = data.phase;
                document.getElementById('phase_id').value = phase.phase_id;
                populateProjectSelect(phase.project_id);
                document.getElementById('phase_name').value = phase.phase_name;
                document.getElementById('phase_description').value = phase.description || '';
                document.getElementById('phase_start_date').value = phase.start_date || '';
                document.getElementById('phase_end_date').value = phase.end_date || '';
                document.getElementById('phase_duration').value = phase.duration || '';
                document.getElementById('phase_status').value = phase.status || 'Not Started';
                
                document.getElementById('phaseModalTitle').textContent = 'Edit Phase';
                document.getElementById('phaseModalBtnText').textContent = 'Save Changes';
                document.getElementById('phaseModal').style.display = 'flex';
            } else {
                showToast(data.message || 'Error fetching phase', 'error');
            }
        });
}

// ------------------ Delete Phase Logic ------------------
function closeDeleteModal() {
    phaseToDelete = null;
    const modal = document.getElementById('deleteModal');
    if (modal) modal.classList.add('hidden');
    document.body.style.overflow = '';
}

function confirmDelete() {
    if (!phaseToDelete) return;

    const btn = document.getElementById('confirmDeleteBtn');
    const originalContent = btn ? btn.innerHTML : '';
    if (btn) { 
        btn.disabled = true; 
        btn.innerHTML = `<svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Deleting...`; 
    }

    const formData = new FormData();
    formData.append('delete_id', phaseToDelete);

    fetch(backendUrl, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // AJAX Success
                showToast(data.message || 'Phase deleted successfully', 'success');
                closeDeleteModal();
                refreshDashboard(); // Refresh UI without reload
            } else {
                showToast(data.message || 'Error deleting phase', 'error');
            }
        })
        .catch(err => {
            showToast('Error deleting phase: ' + err.message, 'error');
        })
        .finally(() => {
            if (btn) { btn.disabled = false; btn.innerHTML = originalContent; }
        });
}

// Close modal on outside click
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('deleteModal');
    if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
        closeDeleteModal();
    }
});

// ------------------ Close Phase Modal ------------------
function closePhaseModal() {
    const form = document.getElementById('phaseForm');
    if (form) form.reset();
    const modal = document.getElementById('phaseModal');
    if (modal) modal.style.display = 'none';
}
document.getElementById('closePhaseModal')?.addEventListener('click', closePhaseModal);
document.getElementById('cancelPhaseModal')?.addEventListener('click', closePhaseModal);

// ------------------ Submit Add/Edit (AJAX) ------------------
document.getElementById('phaseForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);

    fetch(backendUrl, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // AJAX Success
                showToast(data.message || 'Phase saved successfully', 'success');
                closePhaseModal();
                refreshDashboard(); // Refresh UI without reload
            } else {
                showToast(data.message || 'Error saving phase', 'error');
            }
        })
        .catch(err => {
            showToast('Error: ' + err.message, 'error');
        });
});

// Export functions to global scope
window.updateTable = updateTable;
window.populateProjectSelect = populateProjectSelect;
window.closeDeleteModal = closeDeleteModal;
window.confirmDelete = confirmDelete;
window.closePhaseModal = closePhaseModal;