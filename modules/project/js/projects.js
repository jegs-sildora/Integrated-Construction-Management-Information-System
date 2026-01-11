const backendUrl = "api/projects.php";
const employeesUrl = "../workforce/api/employees.php?action=list&status=Active";
let projectToDelete = null;

// Safely parse JSON responses — log raw text when parsing fails (helps debug HTML/PHP errors)
async function parseJSONResponse(res) {
    const ct = res.headers.get('content-type') || '';
    const text = await res.text();
    // If server declares JSON or the body looks like JSON, try to parse.
    if (ct.includes('application/json') || text.trim().startsWith('{') || text.trim().startsWith('[')) {
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Invalid JSON response:', text);
            throw new Error('Invalid JSON response from server');
        }
    }
    // Otherwise log the non-JSON response for easier debugging (likely HTML error/redirect)
    console.error('Non-JSON response received from', res.url, text);
    throw new Error('Expected JSON response but received non-JSON content');
}

// ------------------ Dashboard Refresh (AJAX) ------------------
// Fetches the current page HTML and updates Table & Stats in place.
async function refreshDashboard() {
    try {
        const response = await fetch(window.location.href);
        const text = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, 'text/html');

        // 1. Update Table Content
        const newTableContainer = doc.getElementById('projectsTableContainer');
        const currentTableContainer = document.getElementById('projectsTableContainer');
        if (newTableContainer && currentTableContainer) {
            currentTableContainer.innerHTML = newTableContainer.innerHTML;
        }

        // 2. Update Statistics
        const stats = ['statTotalProjects', 'statActiveProjects', 'statCompletedProjects', 'statTotalBudget'];
        stats.forEach(id => {
            const newEl = doc.getElementById(id);
            const currentEl = document.getElementById(id);
            if (newEl && currentEl) {
                currentEl.textContent = newEl.textContent;
            }
        });

        // 3. Re-apply current filters
        filterProjects();
        
    } catch (err) {
        console.error("Failed to refresh dashboard:", err);
        showToast("Dashboard updated, but UI refresh failed. Please reload manually.", "error");
    }
}

// ------------------ Event Delegation (Edit & Delete) ------------------
// We attach this to the container so it works even after Table Refresh
document.addEventListener('click', function(e) {
    const editBtn = e.target.closest('.edit-btn');
    const deleteBtn = e.target.closest('.delete-btn');

    if (editBtn) {
        handleEditProject(editBtn.dataset.id);
    } else if (deleteBtn) {
        projectToDelete = deleteBtn.dataset.id;
        document.getElementById('deleteProjectName').textContent = deleteBtn.dataset.name;
        document.getElementById('deleteModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
});

// ------------------ Search & Filter ------------------
function filterProjects() {
    const searchTerm = document.getElementById('projectSearch')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('projectStatusFilter')?.value.toLowerCase() || '';
    const budgetFilter = document.getElementById('projectBudgetFilter')?.value || '';
    
    const rows = document.querySelectorAll('.project-row');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const name = row.dataset.name || '';
        const manager = row.dataset.manager || '';
        const status = row.dataset.status || '';
        const budget = parseFloat(row.dataset.budget) || 0;
        
        // Search match (project name or manager)
        const searchMatch = !searchTerm || name.includes(searchTerm) || manager.includes(searchTerm);
        
        // Status match
        const statusMatch = !statusFilter || status === statusFilter;
        
        // Budget match
        let budgetMatch = true;
        if (budgetFilter === 'low') {
            budgetMatch = budget < 1000000;
        } else if (budgetFilter === 'mid') {
            budgetMatch = budget >= 1000000 && budget <= 5000000;
        } else if (budgetFilter === 'high') {
            budgetMatch = budget > 5000000;
        }
        
        // Show/hide row
        if (searchMatch && statusMatch && budgetMatch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Show no results message if needed
    const tableBody = document.getElementById('projectsTableBody');
    let noResultsRow = document.getElementById('noResultsRow');
    
    if (visibleCount === 0 && rows.length > 0) {
        if (!noResultsRow) {
            noResultsRow = document.createElement('tr');
            noResultsRow.id = 'noResultsRow';
            noResultsRow.innerHTML = `
                <td colspan="7" class="px-6 py-12 text-center">
                    <div class="text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <p class="text-sm font-medium">No projects match your filters</p>
                        <p class="text-xs mt-1">Try adjusting your search or filter criteria</p>
                    </div>
                </td>
            `;
            tableBody?.appendChild(noResultsRow);
        }
        noResultsRow.style.display = '';
    } else if (noResultsRow) {
        noResultsRow.style.display = 'none';
    }
}

document.getElementById('projectSearch')?.addEventListener('input', filterProjects);
document.getElementById('projectStatusFilter')?.addEventListener('change', filterProjects);
document.getElementById('projectBudgetFilter')?.addEventListener('change', filterProjects);

// ------------------ Add Project ------------------
document.getElementById('addProjectBtn')?.addEventListener('click', function() {
    document.getElementById('projectForm').reset();
    document.getElementById('projectModalTitle').textContent = 'Add Project';
    document.getElementById('projectModalBtnText').textContent = 'Add Project';
    document.getElementById('project_id').value = '';
    
    // Clear budget display and hidden raw value
    if (document.getElementById('total_budget')) document.getElementById('total_budget').value = '';
    if (document.getElementById('total_budget_display')) document.getElementById('total_budget_display').value = '';

    // Get next project code
    fetch(backendUrl + '?get_next_id=1')
        .then(parseJSONResponse)
        .then(data => {
            if (data.success) document.getElementById('project_code').value = data.project_code;
        }).catch(err => console.error('Get next project code error:', err));

    // Fetch active employees for manager select (populate fallback select for older code)
    fetch(employeesUrl)
        .then(parseJSONResponse)
        .then(data => {
            if (data.success) {
                const employees = data.data || [];
                const select = document.getElementById('managerSelect');
                if (select) select.innerHTML = '<option value="">Select Project Manager</option>';
                employees.forEach(emp => {
                    const fullName = emp.first_name + ' ' + emp.last_name;
                    if (select) {
                        const opt = document.createElement('option');
                        opt.value = emp.employee_id;
                        opt.textContent = `${fullName} (${emp.employee_code})`;
                        select.appendChild(opt);
                    }
                });
            }
        }).catch(err => console.error('Fetch employees error:', err));

    document.getElementById('projectModal').style.display = 'flex';
});

// ------------------ Edit Project Logic ------------------
function handleEditProject(projectId) {
    fetch(backendUrl + '?fetch_id=' + projectId)
        .then(parseJSONResponse)
        .then(data => {
            if (data.success) {
                const project = data.project;
                document.getElementById('project_id').value = project.project_id;
                document.getElementById('project_code').value = project.project_code;
                document.getElementById('project_name').value = project.project_name;
                document.getElementById('description').value = project.description || '';
                document.getElementById('location').value = project.location || '';
                document.getElementById('start_date').value = project.start_date || '';
                document.getElementById('end_date').value = project.end_date || '';
                document.getElementById('status').value = project.status || 'Planning';
                // Raw hidden value
                document.getElementById('total_budget').value = project.total_budget || '';
                // Formatted display
                const tbDisplay = document.getElementById('total_budget_display');
                if (tbDisplay) {
                    const raw = (project.total_budget || '').toString();
                    tbDisplay.value = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
                }

                // Set hidden manager id (will be used by datalist script or fallback select)
                const mgrHidden = document.getElementById('project_manager_id_hidden');
                const mgrInput = document.getElementById('managerInput');
                if (mgrHidden) mgrHidden.value = project.project_manager_id || '';

                // Fetch employees and populate manager select/datalist, and select the saved manager
                fetch(employeesUrl)
                    .then(parseJSONResponse)
                    .then(empData => {
                        if (empData.success) {
                            const employees = empData.data || [];
                            const select = document.getElementById('managerSelect');
                            const managerList = document.getElementById('managerList');
                            const managerMap = {};
                            if (select) select.innerHTML = '<option value="">Select Project Manager</option>';
                            if (managerList) managerList.innerHTML = '';

                            employees.forEach(emp => {
                                const fullName = emp.first_name + ' ' + emp.last_name;
                                const label = `${fullName} (${emp.employee_code || ''})`;
                                // fallback select
                                if (select) {
                                    const opt = document.createElement('option');
                                    opt.value = emp.employee_id;
                                    opt.textContent = label;
                                    if (project.project_manager_id == emp.employee_id) opt.selected = true;
                                    select.appendChild(opt);
                                }
                                // datalist option
                                if (managerList) {
                                    const opt2 = document.createElement('option');
                                    opt2.value = label;
                                    managerList.appendChild(opt2);
                                    managerMap[label] = emp.employee_id;
                                }
                                // if this is the saved manager, set input display
                                if (project.project_manager_id == emp.employee_id && mgrInput) {
                                    mgrInput.value = label;
                                }
                            });

                            // ensure hidden field is synced (if managerInput didn't set it)
                            if (mgrHidden && mgrInput && mgrInput.value && managerMap[mgrInput.value]) {
                                mgrHidden.value = managerMap[mgrInput.value];
                            }

                            document.getElementById('projectModalTitle').textContent = 'Edit Project';
                            document.getElementById('projectModalBtnText').textContent = 'Save Changes';
                            document.getElementById('projectModal').style.display = 'flex';
                        }
                    }).catch(err => console.error('Fetch employees error (edit):', err));
            } else {
                showToast(data.message || 'Error fetching project', 'error');
            }
        }).catch(err => console.error('Fetch project error:', err));
}

// ------------------ Delete Project Logic ------------------
function closeDeleteModal() {
    projectToDelete = null;
    document.getElementById('deleteModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function confirmDelete() {
    if (!projectToDelete) return;

    const btn = document.getElementById('confirmDeleteBtn');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Deleting...`;

    const formData = new FormData();
    formData.append('delete_id', projectToDelete);

    fetch(backendUrl, { method: 'POST', body: formData })
        .then(parseJSONResponse)
        .then(data => {
            if (data.success) {
                showToast(data.message || 'Project deleted successfully', 'success');
                closeDeleteModal();
                refreshDashboard();
            } else {
                showToast(data.message || 'Error deleting project', 'error');
            }
        })
        .catch(err => {
            showToast('Error deleting project: ' + err.message, 'error');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalContent;
        });
}

// Close delete modal listeners
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('deleteModal').classList.contains('hidden')) {
        closeDeleteModal();
    }
});

// ------------------ Close Project Modal ------------------
function closeProjectModal() {
    document.getElementById('projectForm').reset();
    if (document.getElementById('total_budget_display')) document.getElementById('total_budget_display').value = '';
    document.getElementById('projectModal').style.display = 'none';
}
document.getElementById('closeProjectModal')?.addEventListener('click', closeProjectModal);
document.getElementById('cancelProjectModal')?.addEventListener('click', closeProjectModal);

// ------------------ Submit Add/Edit (AJAX) ------------------
document.getElementById('projectForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);

    fetch(backendUrl, { method: 'POST', body: formData })
        .then(parseJSONResponse)
        .then(data => {
            if (data.success) {
                showToast(data.message || 'Project saved successfully', 'success');
                closeProjectModal();
                refreshDashboard();
            } else {
                showToast(data.message || 'Error saving project', 'error');
            }
        })
        .catch(err => {
            showToast('Error: ' + err.message, 'error');
        });
});