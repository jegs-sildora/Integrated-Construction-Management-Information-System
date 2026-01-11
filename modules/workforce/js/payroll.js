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
    currentPayslipIndex: null,
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

                // Add per-employee print buttons if employee_id exists
                // Re-render rows with action button to the right
                const rows = employees.map(emp => {
                    const canPrint = emp.employee_id || emp.id || emp.emp_id;
                    const empId = emp.employee_id || emp.id || emp.emp_id || '';
                    return `
                        <tr class="border-b border-gray-50 hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <p class="font-bold text-gray-900 text-xs">${emp.fullname}</p>
                                <p class="text-[10px] text-gray-500 font-mono">${emp.code}</p>
                            </td>
                            <td class="px-4 py-3 text-center text-xs text-gray-600">${emp.days_worked}</td>
                            <td class="px-4 py-3 text-center text-xs ${emp.ot_hours > 0 ? 'text-[#e9922c] font-bold' : 'text-gray-300'}">${emp.ot_hours > 0 ? emp.ot_hours : '-'}</td>
                            <td class="px-4 py-3 text-right text-xs font-mono text-gray-500">₱${this.formatMoney(emp.daily_rate)}</td>
                            <td class="px-6 py-3 text-right font-mono text-xs font-medium text-blue-800">₱${this.formatMoney(emp.gross_pay)}</td>
                            <td class="px-6 py-3 text-right font-mono text-xs text-red-600">(${this.formatMoney(emp.deductions)})</td>
                            <td class="px-6 py-3 text-right font-mono text-sm font-bold text-[#e9922c]">₱${this.formatMoney(emp.net_pay)}</td>
                            <td class="px-4 py-3 text-center w-20">
                                ${canPrint ? `<button onclick="window.open('download_payslip_pdf.php?period_id=${periodId}&employee_id=${empId}','_blank')" class=\"text-gray-400 hover:text-[#e9922c] p-2 rounded-full hover:bg-white transition-all\" title=\"Print Payslip\"><i data-lucide=\"printer\" class=\"w-4 h-4\"></i></button>` : ''}
                            </td>
                        </tr>
                    `;
                }).join('');
                tbody.innerHTML = rows;

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
        this.currentPayslipIndex = index;
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

        printPayslip() {
                const idx = this.currentPayslipIndex;
                if (idx === null || typeof idx === 'undefined') return alert('No payslip selected for printing.');
                const emp = this.state.data[idx];
                if(!emp) return alert('Employee data missing.');

                const projectEl = document.getElementById('projectSelector');
                const projectName = projectEl && projectEl.options.length > 0 ? projectEl.options[projectEl.selectedIndex].text : ('Project ID: ' + this.state.projectId);
                const period = this.state.meta.period_label || '-';
                const dateGen = new Date().toLocaleString();

                // Build single-row payload congruent with exportPDF
                const payload = {
                    projectName,
                    period,
                    dateGen,
                    totals: {
                        gross: emp.gross_pay || 0,
                        deductions: emp.deductions || 0,
                        net: emp.net_pay || 0,
                        count: 1
                    },
                    data: [
                        {
                            code: emp.code || '',
                            fullname: emp.fullname || '',
                            days_worked: emp.days_worked || '-',
                            ot_hours: emp.ot_hours || '-',
                            daily_rate: emp.daily_rate || emp.basic_pay || 0,
                            gross_pay: emp.gross_pay || 0,
                            deductions: emp.deductions || 0,
                            net_pay: emp.net_pay || 0
                        }
                    ]
                };

                // Submit as POST to server printable endpoint and open in new tab
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'download_payslip_pdf.php';
                form.target = '_blank';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'payload';
                input.value = JSON.stringify(payload);
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
                form.remove();
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
                if (!this.state.data || this.state.data.length === 0) return alert('No data to print.');
                const projectEl = document.getElementById('projectSelector');
                const projectName = projectEl && projectEl.options.length > 0 ? projectEl.options[projectEl.selectedIndex].text : 'Project ID: ' + this.state.projectId;
                const period = this.state.meta.period_label || 'Unknown Period';
                const dateGen = new Date().toLocaleString();
                const totals = this.state.totals || { gross: 0, deductions: 0, net: 0, count: 0 };

                let rows = '';
                this.state.data.forEach(emp => {
                        rows += `
                                <tr>
                                        <td class="px-4 py-2 text-sm border border-slate-200 text-slate-800 font-mono">${emp.code}</td>
                                        <td class="px-4 py-2 text-sm border border-slate-200 text-slate-800">${emp.fullname}</td>
                                        <td class="px-4 py-2 text-sm border border-slate-200 text-center">${emp.days_worked}</td>
                                        <td class="px-4 py-2 text-sm border border-slate-200 text-center">${emp.ot_hours || '-'}</td>
                                        <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono">₱${this.formatMoney(emp.daily_rate)}</td>
                                        <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono text-blue-800">₱${this.formatMoney(emp.gross_pay)}</td>
                                        <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono text-red-600">₱${this.formatMoney(emp.deductions)}</td>
                                        <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono font-bold text-[#e9922c]">₱${this.formatMoney(emp.net_pay)}</td>
                                </tr>
                        `;
                });

                const html = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payroll Report - ${projectName}</title>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; padding: 40px; }
        .report-container { max-width: 1000px; margin: 0 auto; background: white; padding: 40px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-radius: 8px; }
        @media print { @page { margin: 0.5in; size: auto; } body { background-color: white !important; color: black !important; padding: 0 !important; -webkit-print-color-adjust: exact; } .report-container { box-shadow: none !important; padding: 0 !important; width: 100% !important; max-width: none !important; } .no-print { display: none !important; } table { width: 100% !important; border-collapse: collapse !important; font-size: 10pt !important; } thead tr { background-color: #f3f4f6 !important; } thead th { border: 1px solid #9ca3af !important; padding: 8px !important; color: black !important; font-weight: bold !important; text-transform: uppercase !important; } tbody td { border: 1px solid #e5e7eb !important; padding: 8px !important; color: black !important; } .print-footer { margin-top: 50px !important; page-break-inside: avoid; } }
        .print-logo { height: 80px; width: auto; margin: 0 auto 10px auto; display: block; }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="text-center border-b-2 border-slate-800 pb-6 mb-8">
            <img src="../../assets/images/nobg_logo.png" alt="ICMIS Logo" class="print-logo">
            <h1 style="font-size:20px;margin:6px 0;font-weight:800;text-transform:uppercase;">Payroll Report</h1>
            <p style="margin:0;color:#6b7280;">Integrated Construction Management Information System</p>
            <p style="margin:2px 0;color:#9ca3af;font-size:12px;">Generated on: ${dateGen}</p>
        </div>

        <div style="display:flex;gap:40px;margin-bottom:24px;font-size:14px;">
            <div style="flex:1;">
                <table style="width:100%;">
                    <tr><td style="font-weight:700;color:#6b7280;padding:4px;width:140px;">Project:</td><td style="font-weight:700;color:#111827;padding:4px;">${projectName}</td></tr>
                    <tr><td style="font-weight:700;color:#6b7280;padding:4px;">Period:</td><td style="color:#111827;padding:4px;">${period}</td></tr>
                </table>
            </div>
            <div style="flex:1;">
                <table style="width:100%;">
                    <tr><td style="font-weight:700;color:#6b7280;padding:4px;width:140px;">Employees:</td><td style="color:#374151;padding:4px;">${totals.count || this.state.data.length}</td></tr>
                    <tr><td style="font-weight:700;color:#6b7280;padding:4px;">Total Net Pay:</td><td style="color:#374151;padding:4px;font-family:monospace;">₱${this.formatMoney(totals.net)}</td></tr>
                </table>
            </div>
        </div>

        <table style="width:100%;border-collapse:collapse;margin-bottom:18px;">
            <thead style="background:#f3f4f6;text-align:left;">
                <tr>
                    <th style="padding:8px;border:1px solid #e5e7eb;width:12%;">Code</th>
                    <th style="padding:8px;border:1px solid #e5e7eb;width:34%;">Employee</th>
                    <th style="padding:8px;border:1px solid #e5e7eb;width:8%;text-align:center;">Days</th>
                    <th style="padding:8px;border:1px solid #e5e7eb;width:8%;text-align:center;">OT</th>
                    <th style="padding:8px;border:1px solid #e5e7eb;width:12%;text-align:right;">Rate</th>
                    <th style="padding:8px;border:1px solid #e5e7eb;width:12%;text-align:right;">Gross</th>
                    <th style="padding:8px;border:1px solid #e5e7eb;width:12%;text-align:right;">Net</th>
                </tr>
            </thead>
            <tbody>
                ${rows}
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="padding:12px;border:1px solid #e5e7eb;text-align:right;font-weight:700;">Grand Totals</td>
                    <td style="padding:12px;border:1px solid #e5e7eb;text-align:right;font-family:monospace;">-</td>
                    <td style="padding:12px;border:1px solid #e5e7eb;text-align:right;font-family:monospace;">₱${this.formatMoney(totals.gross)}</td>
                    <td style="padding:12px;border:1px solid #e5e7eb;text-align:right;font-weight:800;color:#e9922c;font-family:monospace;">₱${this.formatMoney(totals.net)}</td>
                </tr>
            </tfoot>
        </table>

        <div class="print-footer" style="margin-top:32px;display:flex;gap:40px;">
            <div style="flex:1;text-align:center;">
                <p style="font-size:11px;font-weight:700;color:#6b7280;">Prepared By:</p>
                <div style="border-bottom:1px solid #111827;width:60%;margin:18px auto 8px auto;height:10px;"></div>
                <p style="font-weight:700;">System Generated</p>
            </div>
            <div style="flex:1;text-align:center;">
                <p style="font-size:11px;font-weight:700;color:#6b7280;">Verified By:</p>
                <div style="border-bottom:1px solid #111827;width:60%;margin:18px auto 8px auto;height:10px;"></div>
                <p style="font-weight:700;">Project Engineer</p>
            </div>
            <div style="flex:1;text-align:center;">
                <p style="font-size:11px;font-weight:700;color:#6b7280;">Approved By:</p>
                <div style="border-bottom:1px solid #111827;width:60%;margin:18px auto 8px auto;height:10px;"></div>
                <p style="font-weight:700;">Project Manager</p>
            </div>
        </div>

        <div class="no-print" style="margin-top:20px;text-align:center;">
            <p style="color:#6b7280;font-size:13px;margin-bottom:8px;">If printing does not start automatically, use the Print option in your browser.</p>
            <button onclick="window.print()" style="padding:8px 16px;background:#2563eb;color:#fff;border-radius:8px;border:none;">Print Report</button>
        </div>
    </div>
    <script>setTimeout(()=>{window.print();},500);</script>
</body>
</html>`;

                // POST payload to server endpoint in a new tab to render printable HTML server-side
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'download_payroll_pdf.php';
                form.target = '_blank';
                const input = document.createElement('input');
                input.type = 'hidden';
                const payload = { projectName, period, dateGen, totals, data: this.state.data };
                input.name = 'payload';
                input.value = JSON.stringify(payload);
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
                form.remove();
    }
};