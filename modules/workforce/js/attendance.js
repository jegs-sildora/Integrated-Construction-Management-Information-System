/**
 * Attendance Management Logic
 * Handles local storage caching, UI interactions, and AJAX submissions.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initialize Icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Restore any unsaved drafts from local storage
    restoreFromLocal();
});

// --- Local Storage Caching ---

function getStorageKey(empId, field) {
    const dateInput = document.getElementById('attendanceDate');
    const date = dateInput ? dateInput.value : new Date().toISOString().split('T')[0];
    return `att_draft_${date}_${empId}_${field}`;
}

function saveToLocal(element) {
    const row = element.closest('tr');
    if (!row) return;

    const empId = row.dataset.id;
    const field = element.name;
    const value = element.value;
    const key = getStorageKey(empId, field);

    if (value) {
        localStorage.setItem(key, value);
    } else {
        localStorage.removeItem(key);
    }
}

function restoreFromLocal() {
    const rows = document.querySelectorAll('.attendance-row');
    const dateInput = document.getElementById('attendanceDate');
    if(!dateInput) return;

    const date = dateInput.value;

    rows.forEach(row => {
        const empId = row.dataset.id;
        const inputs = row.querySelectorAll('input, select');

        inputs.forEach(input => {
            const key = `att_draft_${date}_${empId}_${input.name}`;
            const savedValue = localStorage.getItem(key);

            if (savedValue !== null) {
                input.value = savedValue;
                // Trigger status change logic if we just restored a status
                if (input.name === 'status') {
                    handleStatusChange(input);
                }
            }
        });
    });
}

function clearLocalData(records) {
    const dateInput = document.getElementById('attendanceDate');
    const date = dateInput ? dateInput.value : '';

    records.forEach(record => {
        const empId = record.employee_id;
        ['time_in', 'time_out', 'status', 'remarks'].forEach(field => {
            localStorage.removeItem(`att_draft_${date}_${empId}_${field}`);
        });
    });
}

// --- UI Interaction ---

window.switchView = function(view) {
    const individualView = document.getElementById('view-individual');
    const groupsView = document.getElementById('view-groups');
    const btnIndividual = document.getElementById('btn-individual');
    const btnGroups = document.getElementById('btn-groups');

    if (individualView && groupsView) {
        individualView.classList.add('hidden');
        groupsView.classList.add('hidden');
        document.getElementById('view-' + view).classList.remove('hidden');
    }

    if (btnIndividual && btnGroups) {
        btnIndividual.className = 'tab-btn inactive px-6 py-3 text-sm font-semibold border-b-2 transition-colors';
        btnGroups.className = 'tab-btn inactive px-6 py-3 text-sm font-semibold border-b-2 transition-colors';
        document.getElementById('btn-' + view).className = 'tab-btn active px-6 py-3 text-sm font-semibold border-b-2 transition-colors';
    }
};

window.toggleGroup = function(gid) {
    const details = document.getElementById(`details-${gid}`);
    const icon = document.getElementById(`icon-${gid}`);
    
    if (details && icon) {
        if (details.classList.contains('open')) {
            details.classList.remove('open');
            icon.style.transform = 'rotate(0deg)';
        } else {
            details.classList.add('open');
            icon.style.transform = 'rotate(180deg)';
        }
    }
};

window.updateDate = function(date) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('date', date);
    urlParams.set('page', 1); // Reset to page 1 on date change
    window.location.search = urlParams.toString();
};

window.handleStatusChange = function(select) {
    const row = select.closest('tr');
    const timeIn = row.querySelector('input[name="time_in"]');
    const timeOut = row.querySelector('input[name="time_out"]');
    const val = select.value;

    // Reset row colors
    row.classList.remove('bg-red-50/50', 'bg-green-50/30');

    if (val === 'Present') {
        row.classList.add('bg-green-50/30');
        timeIn.disabled = false;
        timeOut.disabled = false;
        if (!timeIn.value) timeIn.value = '08:00';
        if (!timeOut.value) timeOut.value = '17:00';
    } else if (val === 'Late') {
        timeIn.disabled = false;
        timeOut.disabled = false;
        if (!timeIn.value) timeIn.value = '09:00';
    } else if (val === 'Absent' || val === 'On Leave') {
        if (val === 'Absent') row.classList.add('bg-red-50/50');
        timeIn.value = '';
        timeOut.value = '';
        timeIn.disabled = true;
        timeOut.disabled = true;
    } else {
        // Default / Empty
        timeIn.disabled = false;
        timeOut.disabled = false;
    }
};

// --- Bulk Marking Helpers ---

function applyPresentToRows(rows, options = { showToast: true }) {
    let count = 0;
    rows.forEach(row => {
        const select = row.querySelector('select[name="status"]');
        if (select && select.value === '') {
            select.value = 'Present';
            handleStatusChange(select);
            saveToLocal(select); // Save status

            // Save auto-filled times
            const timeIn = row.querySelector('input[name="time_in"]');
            const timeOut = row.querySelector('input[name="time_out"]');
            if (timeIn) saveToLocal(timeIn);
            if (timeOut) saveToLocal(timeOut);

            count++;
        }
    });

    if (count > 0 && options.showToast && typeof showToast === 'function') {
        showToast(`${count} employees marked as Present`, 'info');
    }
}

window.markAllPresent = function(context = 'individual') {
    // Immediately apply to visible rows for instant UI feedback
    const selector = context === 'individual' ? '#view-individual .attendance-row' : '.attendance-row';
    const rows = document.querySelectorAll(selector);
    applyPresentToRows(rows, { showToast: false });

    // For 'individual' context we want to mark ALL employees (not just the current page)
        if (context === 'individual') {
        const dateInput = document.getElementById('attendanceDate');
        const date = dateInput ? dateInput.value : new Date().toISOString().split('T')[0];
        const projectId = (typeof window.SELECTED_PROJECT_ID !== 'undefined') ? window.SELECTED_PROJECT_ID : 0;

            // Updated to use API Gateway
            fetch(`${window.GATEWAY_URL}workforce/attendance?action=list&date=${encodeURIComponent(date)}&project_id=${encodeURIComponent(projectId)}`, {
                headers: { 'Authorization': `Bearer ${window.AUTH_TOKEN}` }
            })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        if (typeof showToast === 'function') showToast(data.message || 'Could not fetch employees', 'error');
                        return;
                    }

                    const employees = data.data || [];
                    if (employees.length === 0) {
                        if (typeof showToast === 'function') showToast('No employees found to mark.', 'warning');
                        return;
                    }

                    employees.forEach(emp => {
                        const empId = emp.employee_id;
                        const keyStatus = `att_draft_${date}_${empId}_status`;
                        const keyIn = `att_draft_${date}_${empId}_time_in`;
                        const keyOut = `att_draft_${date}_${empId}_time_out`;
                        localStorage.setItem(keyStatus, 'Present');
                        localStorage.setItem(keyIn, emp.time_in || '08:00');
                        localStorage.setItem(keyOut, emp.time_out || '17:00');
                    });

                    if (typeof showToast === 'function') showToast(`${employees.length} employees marked as Present (draft)`, 'info');
                })
                .catch(err => {
                    console.error('Error fetching employees for draft mark-all:', err);
                    if (typeof showToast === 'function') showToast('System error occurred', 'error');
                });
    }
};

window.markGroupPresent = function(gid) {
    const rows = document.querySelectorAll(`.group-row-${gid}`);
    applyPresentToRows(rows);
    
    // Auto-expand group to show changes
    const details = document.getElementById(`details-${gid}`);
    if (details && !details.classList.contains('open')) {
        toggleGroup(gid);
    }
};

// --- AJAX Submission ---

window.saveAllAttendance = function() {
    const dateInput = document.getElementById('attendanceDate');
    const date = dateInput ? dateInput.value : '';
    
    // Get project ID from a global variable set in the PHP file
    // Fallback to 0 if not defined
    const projectId = (typeof window.SELECTED_PROJECT_ID !== 'undefined') ? window.SELECTED_PROJECT_ID : 0;

    const rows = document.querySelectorAll('.attendance-row');
    let records = [];
    const seen = new Set();

    // Collect records from visible rows (per-page)
    rows.forEach(row => {
        const empId = row.dataset.id;
        const statusSelect = row.querySelector('select[name="status"]');
        const timeInInput = row.querySelector('input[name="time_in"]');
        const timeOutInput = row.querySelector('input[name="time_out"]');
        const remarksInput = row.querySelector('input[name="remarks"]');

        if (statusSelect && statusSelect.value) {
            records.push({
                employee_id: empId,
                status: statusSelect.value,
                time_in: timeInInput ? timeInInput.value : '',
                time_out: timeOutInput ? timeOutInput.value : '',
                remarks: remarksInput ? remarksInput.value : ''
            });
            seen.add(String(empId));
        }
    });

    // Also include any drafts saved in localStorage for this date (these represent employees on other pages)
    const prefix = `att_draft_${date}_`;
    const drafts = {};
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (!key || !key.startsWith(prefix)) continue;
        // key format: att_draft_{date}_{empId}_{field}
        const parts = key.split('_');
        // parts: ['att','draft','YYYY-MM-DD','{empId}','{field}']
        const empId = parts[3];
        const field = parts.slice(4).join('_');
        drafts[empId] = drafts[empId] || { employee_id: empId, status: '', time_in: '', time_out: '', remarks: '' };
        const val = localStorage.getItem(key);
        if (field === 'status') drafts[empId].status = val;
        else if (field === 'time_in') drafts[empId].time_in = val;
        else if (field === 'time_out') drafts[empId].time_out = val;
        else if (field === 'remarks') drafts[empId].remarks = val;
    }

    Object.keys(drafts).forEach(empId => {
        if (!seen.has(String(empId)) && drafts[empId].status) {
            records.push(drafts[empId]);
            seen.add(String(empId));
        }
    });

    if (records.length === 0) {
        if(typeof showToast === 'function') showToast('No attendance data to save.', 'warning');
        return;
    }

    const btn = document.querySelector('button[onclick="saveAllAttendance()"]');
    const originalText = btn ? btn.innerHTML : 'Save Changes';
    
    if(btn) {
        btn.innerHTML = '<div class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>Saving...';
        btn.disabled = true;
    }

    // Updated to use API Gateway
    fetch(`${window.GATEWAY_URL}workforce/attendance`, {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${window.AUTH_TOKEN}`
        },
        body: JSON.stringify({
            action: 'save_bulk',
            date: date,
            project_id: projectId,
            records: records
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if(typeof showToast === 'function') showToast('Attendance saved successfully!', 'success');
            
            clearLocalData(records);
            
            // Reload page shortly after to reflect changes
            setTimeout(() => location.reload(), 800);
        } else {
            if(typeof showToast === 'function') showToast(data.message || 'Error saving data', 'error');
            
            if(btn) {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    })
    .catch(err => {
        console.error(err);
        if(typeof showToast === 'function') showToast('System connection error occurred', 'error');
        
        if(btn) {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
};