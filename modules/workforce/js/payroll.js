/**
 * Payroll Module Logic
 * Location: /workforce/js/payroll.js
 * Version: 2.3 (Uniform Tables & View Modal)
 */

const Payroll = {
    state: {
        projectId: 0,
        month: new Date().toISOString().slice(0, 7),
        data: [],
        meta: {},
        issues: []
    },
    period: 1,
    config: {
        sss: { ee_rate: 0.045, max_msc: 30000, min_msc: 4000 },
        philhealth: { rate: 0.05, ee_share: 0.5, min_salary: 10000, max_salary: 100000 },
        pagibig: { rate: 0.02, max_contribution: 200 }
    },

    init() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
        const projSelect = document.getElementById('projectSelector');
        const monthInput = document.getElementById('payrollMonth');
        const periodSelect = document.getElementById('payrollPeriod');

        if (typeof window.SELECTED_PROJECT_ID !== 'undefined') {
            this.state.projectId = Number(window.SELECTED_PROJECT_ID) || 0;
            if(projSelect) projSelect.value = this.state.projectId;
        }

        if (monthInput) monthInput.addEventListener('change', (e) => { this.state.month = e.target.value; this.loadData(); });
        if (periodSelect) periodSelect.addEventListener('change', (e) => { this.period = Number(e.target.value); this.loadData(); });

        this.loadData();
    },

    // --- MAIN WORKSHEET LOGIC ---
    async loadData() {
        const tableBody = document.getElementById('payrollTableBody');
        const loading = document.getElementById('loadingBar');
        const footer = document.getElementById('payrollTableFoot');
        const alertBox = document.getElementById('healthCheckAlert');
        const lockBtn = document.getElementById('btnLockPayroll');
        const statusBadge = document.getElementById('periodStatusBadge');

        if (this.state.projectId === 0) { this.renderEmptyState('Select a project.'); return; }

        loading.classList.remove('hidden');
        tableBody.innerHTML = ''; 
        footer.classList.add('hidden');
        alertBox.classList.add('hidden');
        
        try {
            const params = new URLSearchParams({ action: 'get_payroll', project_id: this.state.projectId, month: this.state.month, period: this.period });
            const res = await fetch(`api/payroll.php?${params.toString()}`);
            const json = await res.json();

            if (json.success) {
                this.state.data = json.data.employees;
                this.state.meta = json.data.meta;
                this.state.totals = json.data.totals;
                this.state.issues = json.data.issues || [];

                document.getElementById('tablePeriodLabel').innerText = json.data.meta.period_label;
                const lockLbl = document.getElementById('lockPeriodLabel');
                if(lockLbl) lockLbl.innerText = json.data.meta.period_label;

                this.renderTable(this.state.data);
                this.updateStats(json.data.totals);
                
                // Update UI Status (Draft/Locked)
                if (statusBadge) {
                    statusBadge.classList.remove('hidden', 'bg-gray-100', 'text-gray-600', 'bg-green-100', 'text-green-800', 'border-green-200', 'border-gray-200');
                    if (this.state.meta.is_locked) {
                        statusBadge.innerText = 'LOCKED & POSTED';
                        statusBadge.classList.add('bg-green-100', 'text-green-800', 'border-green-200');
                        lockBtn.disabled = true;
                        lockBtn.classList.add('bg-gray-300', 'cursor-not-allowed');
                        document.getElementById('btnLockText').innerText = "Finalized";
                    } else {
                        statusBadge.innerText = 'DRAFT MODE';
                        statusBadge.classList.add('bg-gray-100', 'text-gray-600', 'border-gray-200');
                        lockBtn.classList.remove('bg-gray-300', 'cursor-not-allowed');
                        lockBtn.disabled = false;
                        document.getElementById('btnLockText').innerText = "Finalize Payroll";
                    }
                }

                if (this.state.issues.length > 0 && !this.state.meta.is_locked) {
                    alertBox.classList.remove('hidden');
                    document.getElementById('healthCheckMsg').innerText = `${this.state.issues.length} Employee(s) have missing logs. Fix in Attendance before locking.`;
                    lockBtn.disabled = true;
                }
                footer.classList.remove('hidden');
            } else {
                this.renderEmptyState(json.message);
            }
        } catch (e) {
            console.error(e);
            this.renderEmptyState('Error loading payroll.');
        } finally {
            loading.classList.add('hidden');
            if(typeof lucide !== 'undefined') lucide.createIcons();
        }
    },

    // --- HISTORY TABLE LOGIC (Uniform Design) ---
    async loadHistory() {
        const tbody = document.getElementById('historyTableBody');
        tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">Loading history...</td></tr>`;
        
        try {
            const params = new URLSearchParams({ action: 'get_history', project_id: this.state.projectId });
            const res = await fetch(`api/payroll.php?${params.toString()}`);
            const json = await res.json();
            
            tbody.innerHTML = '';
            if(json.success && json.data.length > 0) {
                json.data.forEach(row => {
                    tbody.innerHTML += `
                        <tr class="hover:bg-orange-50/50 border-b border-gray-100 transition-colors group cursor-pointer" onclick="Payroll.openHistoryDetails(${row.period_id})">
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-800 text-sm">${row.label}</p>
                                <p class="text-xs text-gray-500">Period ID: #${row.period_id}</p>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600 font-medium">
                                ${row.pay_date}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs font-mono font-bold">${row.employee_count}</span>
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-[#e9922c] font-bold">
                                ₱${this.formatMoney(row.total_net)}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 rounded text-xs font-bold bg-green-100 text-green-800 uppercase border border-green-200">${row.status}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button class="text-gray-400 hover:text-[#e9922c] p-2 rounded-full hover:bg-white transition-all group-hover:shadow-sm">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                if(typeof lucide !== 'undefined') lucide.createIcons();
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-8 text-center text-gray-400 flex flex-col items-center gap-2"><i data-lucide="archive" class="w-8 h-8 text-gray-300"></i><p>No closed payroll records found.</p></td></tr>`;
                if(typeof lucide !== 'undefined') lucide.createIcons();
            }
        } catch (e) {
            console.error(e);
            tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-8 text-center text-red-400">Error loading history.</td></tr>`;
        }
    },

    // --- HISTORY DETAILS MODAL LOGIC ---
    async openHistoryDetails(periodId) {
        const modal = document.getElementById('historyDetailsModal');
        const title = document.getElementById('histModalTitle');
        const subtitle = document.getElementById('histModalSubtitle');
        const tbody = document.getElementById('histModalBody');
        
        // Reset Modal State
        title.innerText = 'Loading...';
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-400">Fetching records...</td></tr>';
        
        // Show Modal
        modal.classList.remove('hidden');
        setTimeout(() => modal.querySelector('.modal-content').classList.add('modal-open'), 10);

        try {
            // Fetch Specific Period Data (Passing period_id to get exact snapshot)
            const params = new URLSearchParams({ 
                action: 'get_payroll', 
                project_id: this.state.projectId, 
                period_id: periodId // Backend must support this
            });
            
            const res = await fetch(`api/payroll.php?${params.toString()}`);
            const json = await res.json();

            if (json.success) {
                const meta = json.data.meta;
                const employees = json.data.employees;
                const totals = json.data.totals;

                title.innerText = `Payroll: ${meta.period_label}`;
                subtitle.innerText = `Posted Record • ${meta.start_date} to ${meta.end_date}`;
                
                // Render Table
                tbody.innerHTML = '';
                employees.forEach(emp => {
                    tbody.innerHTML += `
                        <tr class="border-b border-gray-50 hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <p class="font-bold text-gray-900 text-xs">${emp.fullname}</p>
                                <p class="text-[10px] text-gray-500 font-mono">${emp.code}</p>
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-gray-600">${emp.days_worked}</td>
                            <td class="px-4 py-3 text-center text-xs ${emp.ot_hours > 0 ? 'text-[#e9922c] font-bold' : 'text-gray-300'}">
                                ${emp.ot_hours > 0 ? emp.ot_hours : '-'}
                            </td>
                            <td class="px-4 py-3 text-right text-xs font-mono text-gray-500">₱${this.formatMoney(emp.daily_rate)}</td>
                            <td class="px-6 py-3 text-right font-mono text-xs font-medium text-blue-800">₱${this.formatMoney(emp.gross_pay)}</td>
                            <td class="px-6 py-3 text-right font-mono text-xs text-red-600">(${this.formatMoney(emp.deductions)})</td>
                            <td class="px-6 py-3 text-right font-mono text-sm font-bold text-[#e9922c]">₱${this.formatMoney(emp.net_pay)}</td>
                        </tr>
                    `;
                });

                // Update Footer Totals
                document.getElementById('histTotalGross').innerText = '₱' + this.formatMoney(totals.gross);
                document.getElementById('histTotalDed').innerText = '-₱' + this.formatMoney(totals.deductions);
                document.getElementById('histTotalNet').innerText = '₱' + this.formatMoney(totals.net);

            } else {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-red-500">${json.message}</td></tr>`;
            }
        } catch (e) {
            console.error(e);
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-red-500">Error loading details.</td></tr>`;
        }
    },

    closeHistoryModal() {
        const modal = document.getElementById('historyDetailsModal');
        modal.querySelector('.modal-content').classList.remove('modal-open');
        setTimeout(() => modal.classList.add('hidden'), 200);
    },

    // --- STANDARD RENDERERS ---
    renderTable(employees) {
        const tbody = document.getElementById('payrollTableBody');
        tbody.innerHTML = '';
        if (!employees || employees.length === 0) { this.renderEmptyState('No data.'); return; }

        employees.forEach((emp, index) => {
            const initials = emp.fullname.split(' ').map(n=>n[0]).join('').substring(0,2).toUpperCase();
            const tr = document.createElement('tr');
            tr.className = "hover:bg-orange-50/50 transition-colors border-b border-gray-100 cursor-pointer group";
            tr.onclick = () => this.openPayslip(index);
            tr.innerHTML = `
                <td class="px-6 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 text-xs font-bold border border-gray-200 group-hover:border-[#e9922c] transition-colors">${initials}</div>
                    <div><p class="font-bold text-gray-900 text-sm">${emp.fullname}</p><p class="text-xs text-gray-500 font-mono">${emp.code}</p></div>
                </td>
                <td class="px-4 py-3 text-center"><span class="bg-gray-100 px-2 py-0.5 rounded text-xs font-mono font-bold text-gray-700">${emp.days_worked}</span></td>
                <td class="px-4 py-3 text-center font-mono text-xs ${emp.ot_hours>0?'text-[#e9922c] font-bold':'text-gray-300'}">${emp.ot_hours||'-'}</td>
                <td class="px-4 py-3 text-right text-xs font-mono text-gray-500">₱${this.formatMoney(emp.daily_rate)}</td>
                <td class="px-6 py-3 text-right font-mono font-medium text-blue-900 bg-blue-50/10 rounded-sm">₱${this.formatMoney(emp.gross_pay)}</td>
                <td class="px-6 py-3 text-right font-mono text-red-600 text-xs">(${this.formatMoney(emp.deductions)})</td>
                <td class="px-6 py-3 text-right font-mono font-bold text-[#e9922c] bg-orange-50/10 rounded-sm">₱${this.formatMoney(emp.net_pay)}</td>
                <td class="px-4 py-3 text-center"><i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 group-hover:text-[#e9922c]"></i></td>
            `;
            tbody.appendChild(tr);
        });
    },

    openPayslip(index) {
        const emp = this.state.data[index];
        if(!emp) return;
        this.setTxt('psName', emp.fullname);
        this.setTxt('psId', emp.code);
        this.setTxt('psPeriod', this.state.meta.period_label || '-');
        
        this.setTxt('psBasic', this.formatMoney(emp.basic_pay));
        this.setTxt('psOtPay', this.formatMoney(emp.ot_pay));
        this.setTxt('psTotalGross', this.formatMoney(emp.gross_pay));
        
        let d = this.state.meta.is_locked ? 
            { sss: emp.sss_deduction, ph: emp.philhealth_deduction, pi: emp.pagibig_deduction } :
            this.calculateContributions(emp.payment_type==='Monthly' ? emp.monthly_salary : emp.gross_pay*2);
            
        this.setTxt('psSSS', this.formatMoney(d.sss));
        this.setTxt('psPhilHealth', this.formatMoney(d.ph));
        this.setTxt('psPagIbig', this.formatMoney(d.pi));
        this.setTxt('psTotalDed', this.formatMoney(emp.deductions));
        this.setTxt('psNet', '₱' + this.formatMoney(emp.net_pay));

        document.getElementById('payslipModal').classList.remove('hidden');
        setTimeout(() => document.querySelector('#payslipModal .modal-content').classList.add('modal-open'), 10);
    },

    closePayslip() {
        const m = document.getElementById('payslipModal');
        m.querySelector('.modal-content').classList.remove('modal-open');
        setTimeout(() => m.classList.add('hidden'), 200);
    },

    openLockModal() {
        if (this.state.meta.is_locked) return;
        const lbl = document.getElementById('lockPeriodLabel');
        const modal = document.getElementById('lockPayrollModal');
        if (lbl) lbl.innerText = this.state.meta.period_label || '';
        if (!modal) return;
        modal.classList.remove('hidden');
        setTimeout(() => {
            const content = modal.querySelector('.modal-content');
            if (content) content.classList.add('modal-open');
        }, 10);
    },
    closeLockModal() {
        const modal = document.getElementById('lockPayrollModal');
        if (!modal) return;
        const content = modal.querySelector('.modal-content');
        if (content) content.classList.remove('modal-open');
        setTimeout(() => { modal.classList.add('hidden'); }, 200);
    },

    async confirmLock() {
        try {
            const fd = new FormData();
            fd.append('action', 'lock_payroll');
            fd.append('project_id', this.state.projectId);
            fd.append('month', this.state.month);
            fd.append('period', this.period);
            const res = await fetch('api/payroll.php', { method: 'POST', body: fd });
            const json = await res.json();
            if(json.success) { this.closeLockModal(); this.loadData(); }
            else alert(json.message);
        } catch(e) { console.error(e); }
    },

    calculateContributions(salary) {
        let s = parseFloat(salary)||0;
        return {
            sss: Math.min(s * this.config.sss.ee_rate, this.config.sss.max_msc * this.config.sss.ee_rate),
            ph: s * this.config.philhealth.rate * this.config.philhealth.ee_share,
            pi: Math.min(s * this.config.pagibig.rate, this.config.pagibig.max_contribution)
        };
    },

    updateStats(t) {
        if(!t) return;
        const set = (id, v, p) => document.getElementById(id).innerText = p + this.formatMoney(v);
        document.getElementById('statEmployees').innerText = t.count;
        set('statGross', t.gross, '₱'); set('statDeductions', t.deductions, '₱'); set('statNet', t.net, '₱');
        set('footGross', t.gross, '₱'); set('footDeductions', t.deductions, '-₱'); set('footNet', t.net, '₱');
        
        let totOt = this.state.data.reduce((a,c)=>a+parseFloat(c.ot_hours||0),0);
        document.getElementById('footOtHrs').innerText = totOt.toFixed(1);
    },

    renderEmptyState(msg) {
        document.getElementById('payrollTableBody').innerHTML = `<tr><td colspan="8" class="px-6 py-12 text-center text-gray-400 bg-white"><div class="flex flex-col items-center"><i data-lucide="folder-open" class="w-10 h-10 text-gray-200 mb-2"></i><p>${msg}</p></div></td></tr>`;
    },
    formatMoney(v) { return parseFloat(v||0).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}); },
    setTxt(id, v) { const e = document.getElementById(id); if(e) e.innerText = v; },
    
    // Keeping PDF Export logic ... (omitted for brevity, same as previous version)
    exportPDF() {
        if (!this.state.data || this.state.data.length === 0) return alert('No data to export.');
        const projectEl = document.getElementById('projectSelector');
        const projectName = projectEl && projectEl.options.length > 0 ? projectEl.options[projectEl.selectedIndex].text : 'Project ID: ' + this.state.projectId;
        const period = this.state.meta.period_label || 'Unknown Period';
        const dateGen = new Date().toLocaleString();
        const totals = this.state.totals || { gross: 0, deductions: 0, net: 0, count: 0 };
        const status = this.state.meta.is_locked ? 'LOCKED / POSTED' : 'DRAFT PREVIEW';
        const statusColor = this.state.meta.is_locked ? 'text-green-700 bg-green-50 border-green-200' : 'text-gray-700 bg-gray-50 border-gray-200';
        let tableRows = '';
        this.state.data.forEach(emp => {
            tableRows += `<tr><td class="px-4 py-2 text-sm border border-slate-200 text-slate-800 font-bold">${emp.fullname} <br><span class="text-xs text-slate-400 font-normal">${emp.code}</span></td><td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-600">${emp.days_worked}</td><td class="px-4 py-2 text-sm border border-slate-200 text-center text-slate-600">${emp.ot_hours || '-'}</td><td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono text-slate-500">${this.formatMoney(emp.daily_rate)}</td><td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono text-blue-800">${this.formatMoney(emp.gross_pay)}</td><td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono text-red-600">(${this.formatMoney(emp.deductions)})</td><td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono font-bold text-slate-900 bg-orange-50">${this.formatMoney(emp.net_pay)}</td></tr>`;
        });
        const htmlContent = `<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Payroll Report</title><script src="https://cdn.tailwindcss.com"></script><style>@media print{@page{margin:0.5in;size:landscape;}}</style></head><body><div class="p-8"><h1 class="text-2xl font-bold">Payroll Report</h1><p class="mb-4">Period: ${period}</p><table class="w-full border-collapse"><thead><tr class="bg-gray-100"><th class="border p-2">Employee</th><th class="border p-2">Days</th><th class="border p-2">OT</th><th class="border p-2">Rate</th><th class="border p-2">Gross</th><th class="border p-2">Ded</th><th class="border p-2">Net</th></tr></thead><tbody>${tableRows}</tbody></table></div><script>setTimeout(()=>{window.print();window.close();},500);</script></body></html>`;
        const win = window.open('', '_blank'); win.document.write(htmlContent); win.document.close();
    }
};