/**
 * Assignments Module Logic
 * Location: /js/assignments.js
 */
const Assignments = {
    state: {
        page: 1,
        projectId: 0,
        mode: 'individual'
    },

    init() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
        
        const filter = document.getElementById('projectContextFilter');
        if (filter) {
            filter.addEventListener('change', (e) => {
                this.state.projectId = e.target.value;
                this.state.page = 1;
                this.loadTable();
            });
            this.state.projectId = filter.value;
        }

        const prev = document.getElementById('prevBtn');
        const next = document.getElementById('nextBtn');
        if (prev) prev.addEventListener('click', () => { if(this.state.page > 1) { this.state.page--; this.loadTable(); } });
        if (next) next.addEventListener('click', () => { this.state.page++; this.loadTable(); });

        const empSelect = document.getElementById('employee_id');
        if (empSelect) {
            empSelect.addEventListener('change', (e) => {
                if (e.target.value) this.fetchEmployeeJobTitle(e.target.value);
            });
        }

        this.loadTable();
    },

    // --- UI Logic ---
    openModal(assignmentId = null) {
        const modal = document.getElementById('assignmentModal');
        const form = document.getElementById('assignmentForm');
        
        form.reset();
        document.getElementById('assignment_id').value = '';
        document.getElementById('statusField').classList.add('hidden');
        
        if (assignmentId) {
            // Edit Mode
            this.fetchDetails(assignmentId);
            document.getElementById('modalTitle').textContent = "Edit Assignment";
            document.getElementById('modeTabs').classList.add('hidden');
            document.getElementById('fieldGroup').classList.add('hidden');
            document.getElementById('fieldIndividual').classList.remove('hidden');
            document.getElementById('roleField').classList.remove('hidden');
            document.getElementById('statusField').classList.remove('hidden');
            // Make employee and role read-only in edit mode
            const _emp = document.getElementById('employee_id');
            const _role = document.getElementById('role');
            if (_emp) _emp.disabled = true;
            if (_role) _role.readOnly = true;
        } else {
            // Create Mode
            document.getElementById('modalTitle').textContent = "New Assignment";
            document.getElementById('modeTabs').classList.remove('hidden');
            document.getElementById('statusField').classList.add('hidden');
            document.getElementById('start_date').valueAsDate = new Date();
            
            if(this.state.projectId > 0) {
                document.getElementById('project_id').value = this.state.projectId;
                this.fetchPhases(this.state.projectId);
            }
            // Ensure employee and role are editable in create mode
            const __emp = document.getElementById('employee_id');
            const __role = document.getElementById('role');
            if (__emp) __emp.disabled = false;
            if (__role) __role.readOnly = false;
            this.switchMode('individual');
        }

        modal.classList.remove('hidden');
        setTimeout(() => modal.querySelector('.modal-content').classList.add('modal-open'), 10);
    },

    closeModal() {
        const modal = document.getElementById('assignmentModal');
        modal.querySelector('.modal-content').classList.remove('modal-open');
        setTimeout(() => modal.classList.add('hidden'), 200);
        // Re-enable fields when modal closes
        const emp = document.getElementById('employee_id');
        const role = document.getElementById('role');
        if (emp) emp.disabled = false;
        if (role) role.readOnly = false;
    },

    switchMode(mode) {
        this.state.mode = mode;
        document.getElementById('modeInput').value = mode;

        const tabInd = document.getElementById('tabIndividual');
        const tabGrp = document.getElementById('tabGroup');
        const fieldInd = document.getElementById('fieldIndividual');
        const fieldGrp = document.getElementById('fieldGroup');
        const roleField = document.getElementById('roleField');
        const empInput = document.getElementById('employee_id');
        const grpInput = document.getElementById('group_id');

        if(mode === 'individual') {
            tabInd.className = "flex-1 py-3 text-sm font-medium text-center tab-active hover:bg-gray-50";
            tabGrp.className = "flex-1 py-3 text-sm font-medium text-center tab-inactive hover:bg-gray-50";
            
            fieldInd.classList.remove('hidden');
            fieldGrp.classList.add('hidden');
            roleField.classList.remove('hidden');
            empInput.required = true;
            grpInput.required = false;
        } else {
            tabGrp.className = "flex-1 py-3 text-sm font-medium text-center tab-active hover:bg-gray-50";
            tabInd.className = "flex-1 py-3 text-sm font-medium text-center tab-inactive hover:bg-gray-50";

            fieldGrp.classList.remove('hidden');
            fieldInd.classList.add('hidden');
            roleField.classList.add('hidden');
            grpInput.required = true;
            empInput.required = false;
        }
    },

    // --- Data Logic ---
    async loadTable() {
        const tbody = document.getElementById('assignmentsTableBody');
        const loader = document.getElementById('tableLoader');
        
        if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">Loading assignments...</td></tr>`;
        if (loader) loader.classList.remove('hidden');
        
        try {
            // Updated to use API Gateway
            const res = await fetch(`${window.GATEWAY_URL}workforce/assignments?project_id=${this.state.projectId}&page=${this.state.page}`, {
                headers: { 'Authorization': `Bearer ${window.AUTH_TOKEN}` }
            });
            const json = await res.json();

            if (json.success) {
                // Handle different response structures safely
                const data = json.data.assignments || json.data || [];
                const meta = json.data.pagination || { current_page: 1, total_pages: 1 };
                this.renderRows(data);
                this.updatePagination(meta);
            } else {
                this.renderEmpty("No assignments found.");
            }
        } catch(e) {
            console.error("Load Table Error:", e);
            this.renderEmpty("Error loading data. See console.");
        } finally {
            if (loader) loader.classList.add('hidden');
        }
    },

    renderRows(data) {
        const tbody = document.getElementById('assignmentsTableBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        
        if(!data || data.length === 0) {
            this.renderEmpty("No assignments found.");
            return;
        }

        data.forEach(item => {
            // SAFETY CHECKS: Prevent crash if data is null
            const fname = item.first_name || 'Unknown';
            const lname = item.last_name || 'Employee';
            const code = item.employee_code || '---';
            const role = item.role || 'Not Specified';
            const phase = item.phase_name || 'General Phase';
            const project = item.project_name || 'Unknown Project';
            
            // Safe initials calculation
            const i1 = fname.charAt(0) || '?';
            const i2 = lname.charAt(0) || '?';
            const initials = (i1 + i2).toUpperCase();

            const statusColor = item.status === 'Active' ? 'bg-green-100 text-green-700' : 
                               (item.status === 'Completed' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700');
            
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-0';
            tr.innerHTML = `
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="employee-avatar shrink-0 text-xs">${initials}</div>
                        <div>
                            <p class="font-bold text-gray-900 text-sm">${fname} ${lname}</p>
                            <p class="text-xs text-gray-500 font-mono">${code}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <p class="text-sm font-medium text-gray-900 truncate max-w-[150px]">${project}</p>
                    <p class="text-xs text-gray-500 truncate max-w-[150px]">${phase}</p>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">${role}</td>
                <td class="px-6 py-4 text-xs text-gray-600">
                    <div><span class="text-gray-400">In:</span> ${item.start_date || '-'}</div>
                    <div><span class="text-gray-400">Out:</span> ${item.end_date || 'Ongoing'}</div>
                </td>
                <td class="px-6 py-4">
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold ${statusColor}">${item.status}</span>
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <button onclick="Assignments.openModal(${item.assignment_id})" class="p-1.5 text-gray-500 hover:text-green-600 hover:bg-green-50"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                        <button onclick="Assignments.delete(${item.assignment_id})" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
        if (typeof lucide !== 'undefined') lucide.createIcons();
    },

    renderEmpty(msg) {
        const tbody = document.getElementById('assignmentsTableBody');
        if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">${msg}</td></tr>`;
    },

    updatePagination(pageData) {
        const current = document.getElementById('currentPage');
        const total = document.getElementById('totalPages');
        const prev = document.getElementById('prevBtn');
        const next = document.getElementById('nextBtn');
        
        if (current) current.textContent = pageData.current_page || pageData.currentPage || 1;
        if (total) total.textContent = pageData.total_pages || pageData.totalPages || 1;
        if (prev) prev.disabled = (pageData.current_page || pageData.currentPage || 1) <= 1;
        if (next) next.disabled = (pageData.current_page || pageData.currentPage || 1) >= (pageData.total_pages || pageData.totalPages || 1);
    },

    async fetchPhases(projectId) {
        const select = document.getElementById('phase_id');
        if (!select) return;
        
        select.innerHTML = '<option>Loading...</option>';
        select.disabled = true;
        
        try {
            const res = await fetch(`${window.GATEWAY_URL}workforce/assignments/phases?project_id=${projectId}`, {
                headers: { 'Authorization': `Bearer ${window.AUTH_TOKEN}` }
            });
            const json = await res.json();
            
            select.innerHTML = '<option value="">-- No Phase / General --</option>';
            if(json.success && json.data) {
                json.data.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.phase_id;
                    opt.textContent = p.phase_name;
                    select.appendChild(opt);
                });
                select.disabled = false;
            }
        } catch(e) { console.error(e); }
    },

    async fetchEmployeeJobTitle(empId) {
        try {
            const res = await fetch(`${window.GATEWAY_URL}workforce/employees/${empId}`, {
                headers: { 'Authorization': `Bearer ${window.AUTH_TOKEN}` }
            });
            if (!res.ok) return; 
            const json = await res.json();
            if (json.success && json.data) {
                const title = json.data.position || json.data.job_title || '';
                const roleInput = document.getElementById('role');
                if (roleInput && roleInput.value === '') {
                    roleInput.value = title;
                }
            }
        } catch (e) {}
    },

    async fetchDetails(id) {
        try {
            const res = await fetch(`${window.GATEWAY_URL}workforce/assignments/${id}`, {
                headers: { 'Authorization': `Bearer ${window.AUTH_TOKEN}` }
            });
            const json = await res.json();
            if(json.success) {
                const data = json.data;
                document.getElementById('assignment_id').value = data.assignment_id;
                document.getElementById('employee_id').value = data.employee_id;
                document.getElementById('project_id').value = data.project_id;
                await this.fetchPhases(data.project_id);
                document.getElementById('phase_id').value = data.phase_id;
                document.getElementById('role').value = data.role;
                document.getElementById('start_date').value = data.start_date;
                document.getElementById('end_date').value = data.end_date;
                document.getElementById('status').value = data.status;
            }
        } catch(e) { console.error(e); }
    },

    async save() {
        const btn = document.getElementById('saveBtn');
        const form = document.getElementById('assignmentForm');
        const originalText = btn.innerText;
        
        btn.innerText = "Processing...";
        btn.disabled = true;

        const formData = new FormData(form);
        const object = {};
        formData.forEach((value, key) => object[key] = value);
        
        const isUpdate = !!object.assignment_id;
        const method = isUpdate ? 'PUT' : 'POST';
        const url = isUpdate 
            ? `${window.GATEWAY_URL}workforce/assignments/${object.assignment_id}` 
            : `${window.GATEWAY_URL}workforce/assignments`;

        try {
            const res = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${window.AUTH_TOKEN}`
                },
                body: JSON.stringify(object)
            });
            const json = await res.json();
            
            if(json.success) {
                if(typeof showToast === 'function') showToast(json.message, 'success');
                else alert(json.message);
                this.closeModal();
                this.loadTable(); 
            } else {
                alert(json.message);
            }
        } catch(e) {
            alert("System Error: " + e.message);
        } finally {
            btn.innerText = originalText;
            btn.disabled = false;
        }
    },

    async delete(id) {
        if(!confirm("Are you sure you want to delete this assignment?")) return;
        try {
            const res = await fetch(`${window.GATEWAY_URL}workforce/assignments/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${window.AUTH_TOKEN}`
                }
            });
            const json = await res.json();
            if(json.success) {
                if(typeof showToast === 'function') showToast("Deleted successfully", 'success');
                this.loadTable();
            } else {
                alert(json.message);
            }
        } catch(e) { alert("Error deleting"); }
    }
};