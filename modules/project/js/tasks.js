/**
 * Tasks page JS (Kanban)
 * - Initializes SortableJS, updates task status via AJAX, and manages modals.
 */
let taskToDelete = null;

// Initialize SortableJS on Kanban columns
function initSortable() {
    const columns = document.querySelectorAll('.kanban-column');
    columns.forEach(column => {
        new Sortable(column, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            filter: '.empty-state',
            onEnd: function(evt) {
                const taskId = evt.item.dataset.id;
                const newStatus = evt.to.dataset.status;
                const oldStatus = evt.from.dataset.status;
                
                if (newStatus !== oldStatus) {
                    updateTaskStatus(taskId, newStatus, evt.item);
                }
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', function() { initSortable(); });

// AJAX refresh: update kanban container and stats
async function refreshDashboard() {
    try {
        const response = await fetch(window.location.href);
        const text = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, 'text/html');

        // 1. Update Kanban Board
        const newBoard = doc.getElementById('kanbanBoardContainer');
        const currentBoard = document.getElementById('kanbanBoardContainer');
        if (newBoard && currentBoard) {
            currentBoard.innerHTML = newBoard.innerHTML;
            // Re-initialize Sortable after replacing DOM
            initSortable();
        }

        // 2. Update Stats
        const statTotal = doc.getElementById('statTotalTasks');
        if (statTotal) document.getElementById('statTotalTasks').textContent = statTotal.textContent;

        const statOverdue = doc.getElementById('statOverdueTasks');
        const statOverdueWrapper = doc.getElementById('statOverdueWrapper');
        if (statOverdue) {
             const overdueCount = parseInt(statOverdue.textContent);
             document.getElementById('statOverdueTasks').textContent = overdueCount;
             
             const currentWrapper = document.getElementById('statOverdueWrapper');
             if (currentWrapper) {
                 if (overdueCount > 0) {
                     currentWrapper.classList.remove('hidden');
                     currentWrapper.classList.add('flex');
                 } else {
                     currentWrapper.classList.add('hidden');
                     currentWrapper.classList.remove('flex');
                 }
             }
        }

    } catch (err) {
        console.error("Dashboard refresh failed", err);
        showToast("Dashboard updated, but UI refresh failed. Please reload manually.", "error");
    }
}

// Event delegation for edit/delete buttons (works after DOM replacement)
document.addEventListener('click', function(e) {
    const editBtn = e.target.closest('.edit-btn');
    const deleteBtn = e.target.closest('.delete-btn');

    if (editBtn) {
        e.stopPropagation();
        handleEditTask(editBtn.dataset.id);
    } else if (deleteBtn) {
        e.stopPropagation();
        taskToDelete = deleteBtn.dataset.id;
        document.getElementById('deleteTaskName').textContent = deleteBtn.dataset.name;
        document.getElementById('deleteModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
});

// Update task status on server when dragged to a new column
function updateTaskStatus(taskId, newStatus, cardElement) {
    const formData = new FormData();
    formData.append('task_id', taskId);
    formData.append('status', newStatus);

    fetch(updateStatusUrl, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(`Task moved to "${newStatus}"`, 'success');
            
            // UI cleanup for immediate feedback (before refresh)
            const targetColumn = cardElement.parentElement;
            const emptyState = targetColumn.querySelector('.empty-state');
            if (emptyState) emptyState.remove();
            
            // Full refresh to ensure stats and badges are correct
            refreshDashboard();
        } else {
            showToast(data.message || 'Failed to update task status', 'error');
            // Revert drag by reloading
            setTimeout(() => window.location.reload(), 500);
        }
    })
    .catch(err => {
        showToast('Error updating task: ' + err.message, 'error');
        setTimeout(() => window.location.reload(), 500);
    });
}

// Dropdown helpers for project/phase/assignee selects
function populateProjectSelect(selectedProjectId = null) {
    const select = document.getElementById('task_project_id');
    if (!select) return;
    select.innerHTML = '<option value="">Select Project</option>';
    projectsData.forEach(proj => {
        const selected = selectedProjectId == proj.project_id ? 'selected' : '';
        select.innerHTML += `<option value="${proj.project_id}" ${selected}>${proj.project_name}</option>`;
    });
}

function populatePhaseSelect(selectedProjectId = null, selectedPhaseId = null) {
    const select = document.getElementById('task_phase_id');
    if (!select) return;
    select.innerHTML = '<option value="">Select Phase (Optional)</option>';
    
    phasesData.forEach(phase => {
        if (!selectedProjectId || phase.project_id == selectedProjectId) {
            const selected = selectedPhaseId == phase.phase_id ? 'selected' : '';
            select.innerHTML += `<option value="${phase.phase_id}" ${selected}>${phase.phase_name} (${phase.project_name})</option>`;
        }
    });
}

function populateAssigneeSelect(selectedEmployeeId = null) {
    const select = document.getElementById('task_assignee');
    if (!select) return;
    select.innerHTML = '<option value="">Unassigned</option>';
    employeesData.forEach(emp => {
        const fullName = emp.first_name + ' ' + emp.last_name;
        const selected = selectedEmployeeId == emp.employee_id ? 'selected' : '';
        select.innerHTML += `<option value="${emp.employee_id}" ${selected}>${fullName} (${emp.employee_code})</option>`;
    });
}

document.getElementById('task_project_id')?.addEventListener('change', function() {
    populatePhaseSelect(this.value);
});

// ------------------ Add Task ------------------
document.getElementById('addTaskBtn')?.addEventListener('click', function() {
    document.getElementById('taskForm').reset();
    document.getElementById('taskModalTitle').textContent = 'Add Task';
    document.getElementById('taskModalBtnText').textContent = 'Add Task';
    document.getElementById('task_id').value = '';
    populateProjectSelect();
    populatePhaseSelect();
    populateAssigneeSelect();
    document.getElementById('taskModal').style.display = 'flex';
});

// ------------------ Edit Task Logic ------------------
function handleEditTask(taskId) {
    fetch(backendUrl + '?fetch_id=' + taskId)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const task = data.task ?? data.record ?? null;
                if (!task) {
                    showToast('Task data not found', 'error');
                    return;
                }
                document.getElementById('task_id').value = task.task_id;
                populateProjectSelect(task.project_id);
                populatePhaseSelect(task.project_id, task.phase_id);
                populateAssigneeSelect(task.assigned_to_employee_id);
                // Sync hidden datalist value and input display (if datalist exists)
                try {
                    const hiddenAssignee = document.getElementById('assigned_to_employee_id_hidden');
                    const assigneeInput = document.getElementById('taskAssigneeInput');
                    if (hiddenAssignee) hiddenAssignee.value = task.assigned_to_employee_id || '';
                    if (assigneeInput && task.assigned_to_employee_id) {
                        // Try to find employee in injected employeesData first
                        const emp = (employeesData || []).find(e => String(e.employee_id) === String(task.assigned_to_employee_id));
                        if (emp) {
                            assigneeInput.value = `${emp.first_name} ${emp.last_name} (${emp.employee_code || ''})`;
                        } else {
                            // Fallback: leave input empty; the task modal's script will fetch and populate datalist and set input if hidden is present
                        }
                    }
                } catch (e) { console.warn('Could not prefill assignee input', e); }
                document.getElementById('task_name').value = task.task_name;
                document.getElementById('task_description').value = task.description || '';
                document.getElementById('task_start_date').value = task.start_date || '';
                document.getElementById('task_due_date').value = task.due_date || '';
                document.getElementById('task_priority').value = task.priority || 'Medium';
                document.getElementById('task_status').value = task.status || 'Not Started';

                document.getElementById('taskModalTitle').textContent = 'Edit Task';
                document.getElementById('taskModalBtnText').textContent = 'Save Changes';
                document.getElementById('taskModal').style.display = 'flex';
            } else {
                showToast(data.message || 'Error fetching task', 'error');
            }
        }).catch(err => {
            console.error('Fetch task error:', err);
            showToast('Error fetching task: ' + (err.message || ''), 'error');
        });
}

// ------------------ Delete Task Logic ------------------
function closeDeleteModal() {
    taskToDelete = null;
    document.getElementById('deleteModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function confirmDelete() {
    if (!taskToDelete) return;

    const btn = document.getElementById('confirmDeleteBtn');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Deleting...`;

    const formData = new FormData();
    formData.append('delete_id', taskToDelete);

    fetch(backendUrl, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // AJAX Success
                showToast(data.message || 'Task deleted successfully', 'success');
                closeDeleteModal();
                refreshDashboard(); // Refresh UI without reload
            } else {
                showToast(data.message || 'Error deleting task', 'error');
            }
        })
        .catch(err => {
            showToast('Error deleting task: ' + err.message, 'error');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalContent;
        });
}

// Close delete modal
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('deleteModal').classList.contains('hidden')) {
        closeDeleteModal();
    }
});

// ------------------ Close Task Modal ------------------
function closeTaskModal() {
    document.getElementById('taskForm').reset();
    document.getElementById('taskModal').style.display = 'none';
}
document.getElementById('closeTaskModal')?.addEventListener('click', closeTaskModal);
document.getElementById('cancelTaskModal')?.addEventListener('click', closeTaskModal);

// ------------------ Submit Add/Edit (AJAX) ------------------
document.getElementById('taskForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);

    fetch(backendUrl, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // AJAX Success
                showToast(data.message || 'Task saved successfully', 'success');
                closeTaskModal();
                refreshDashboard(); // Refresh UI without reload
            } else {
                showToast(data.message || 'Error saving task', 'error');
            }
        })
        .catch(err => {
            showToast('Error: ' + err.message, 'error');
        });
});