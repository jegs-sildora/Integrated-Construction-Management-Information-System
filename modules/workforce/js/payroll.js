/**
 * Payroll Module Logic
 * Location: /js/payroll.js
 */
const Payroll = {
    state: {
        projectId: 0,
        month: new Date().toISOString().slice(0, 7), // YYYY-MM
        data: [] // Store fetched data here for modal use
    },

    // Pagination settings
    perPage: 10,
    currentPage: 1,

    init() {
        if (typeof lucide !== 'undefined') lucide.createIcons();

        const projSelect = document.getElementById('projectSelector');
        const monthInput = document.getElementById('payrollMonth');

        if (projSelect) {
            const ctxId = (typeof window.SELECTED_PROJECT_ID !== 'undefined' && Number(window.SELECTED_PROJECT_ID) > 0) ? Number(window.SELECTED_PROJECT_ID) : null;
            this.state.projectId = ctxId ?? Number(projSelect.value || 0);
            if (ctxId !== null) projSelect.value = ctxId;
            projSelect.addEventListener('change', (e) => {
                this.state.projectId = Number(e.target.value);
                this.loadData();
            });
        } else if (typeof window.SELECTED_PROJECT_ID !== 'undefined') {
            this.state.projectId = Number(window.SELECTED_PROJECT_ID) || 0;
        }

        if (monthInput) {
            monthInput.addEventListener('change', (e) => {
                this.state.month = e.target.value;
                this.loadData();
            });
        }
        
        window.addEventListener('afterprint', () => {
            document.body.classList.remove('printing-payslip');
        });

        this.loadData();
    },

    async loadData() {
        const tableBody = document.getElementById('payrollTableBody');
        const footer = document.getElementById('payrollTableFoot');
        const loading = document.getElementById('loadingBar');
        
        const projectId = Number(this.state.projectId || 0);
        if (projectId === 0) {
            tableBody.innerHTML = `<tr><td colspan="9" class="px-6 py-12 text-center text-gray-400">Please select a project to view payroll.</td></tr>`;
            this.updateStats({gross: 0, deductions: 0, net: 0, count: 0});
            footer.classList.add('hidden');
            return;
        }

        loading.classList.remove('hidden');
        tableBody.innerHTML = `<tr><td colspan="9" class="px-6 py-12 text-center text-gray-500">Calculating payroll...</td></tr>`;
        footer.classList.add('hidden');

        try {
            const res = await fetch(`api/payroll.php?action=get_payroll&project_id=${encodeURIComponent(projectId)}&month=${encodeURIComponent(this.state.month)}`);
            const json = await res.json();

            if (json && json.success) {
                this.state.data = json.data.employees;
                    this.currentPage = 1;
                    this.renderTable(json.data.employees);
                this.updateStats(json.data.totals);

                const periodLabelEl = document.getElementById('tablePeriodLabel');
                if (periodLabelEl) periodLabelEl.innerText = json.data.period_label;
                const printPeriod = document.getElementById('printPeriod');
                if (printPeriod) printPeriod.innerText = "Period: " + json.data.period_label;

                const projSelect = document.getElementById('projectSelector');
                if (projSelect) {
                    const projName = projSelect.options[projSelect.selectedIndex].text;
                    const printProjectName = document.getElementById('printProjectName');
                    if (printProjectName) printProjectName.innerText = projName;
                }

                footer.classList.remove('hidden');
            } else {
                const msg = (json && json.message) ? json.message : 'No data returned.';
                tableBody.innerHTML = `<tr><td colspan="9" class="px-6 py-8 text-center text-red-500">${msg}</td></tr>`;
            }
        } catch (e) {
            console.error(e);
            tableBody.innerHTML = `<tr><td colspan="9" class="px-6 py-8 text-center text-red-500">System Error: Could not calculate payroll.</td></tr>`;
        } finally {
            loading.classList.add('hidden');
        }
    },

    renderTable(employees) {
        const tbody = document.getElementById('payrollTableBody');
        tbody.innerHTML = '';

        if (employees.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="px-6 py-12 text-center text-gray-400">No active employees found for this project.</td></tr>`;
            return;
        }

        const total = employees.length;
        const start = (this.currentPage - 1) * this.perPage;
        const end = Math.min(start + this.perPage, total);
        const pageItems = employees.slice(start, end);

        pageItems.forEach((emp, idx) => {
            const index = start + idx; // original index in full data
            const initials = emp.fullname.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-0 cursor-pointer group';
            tr.onclick = () => this.openPayslip(index);

            tr.innerHTML = `
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold group-hover:bg-[#e9922c] group-hover:text-white transition-colors">
                            ${initials}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">${emp.fullname}</p>
                            <p class="text-xs text-gray-500 font-mono">${emp.code}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-gray-600 text-xs">${emp.role}</td>
                <td class="px-6 py-4 text-center">
                    <span class="font-semibold text-green-600 bg-green-50 px-2 py-0.5 rounded">${emp.days_worked}</span>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="text-gray-400">${emp.days_absent}</span>
                </td>
                <td class="px-6 py-4 text-right font-mono text-gray-500 text-xs">₱${this.formatMoney(emp.daily_rate)}</td>
                <td class="px-6 py-4 text-right font-mono font-medium text-gray-700">₱${this.formatMoney(emp.gross_pay)}</td>
                <td class="px-6 py-4 text-right font-mono text-red-600 text-xs">-₱${this.formatMoney(emp.deductions)}</td>
                <td class="px-6 py-4 text-right font-mono font-bold text-[#e9922c]">₱${this.formatMoney(emp.net_pay)}</td>
                <td class="px-6 py-4 text-center text-gray-300 group-hover:text-[#e9922c]">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </td>
            `;
            tbody.appendChild(tr);
        });

        // render pagination controls
        this.renderPagination(employees.length);

        if (typeof lucide !== 'undefined') lucide.createIcons();
    },

    // --- PAYSLIP MODAL LOGIC ---
    openPayslip(index) {
        const emp = this.state.data[index];
        if(!emp) return;

        // 1. Populate Text Fields
        const periodLabel = document.getElementById('tablePeriodLabel').innerText;
        this.setTxt('psPeriod', periodLabel);
        this.setTxt('psDate', 'Date Generated: ' + new Date().toLocaleDateString());
        
        this.setTxt('psName', emp.fullname);
        this.setTxt('psId', emp.code);
        this.setTxt('psRole', emp.role);
        this.setTxt('psRate', this.formatMoney(emp.daily_rate));
        
        this.setTxt('psDays', emp.days_worked);
        const basicPay = parseFloat(emp.days_worked) * parseFloat(emp.daily_rate);
        this.setTxt('psBasic', '₱' + this.formatMoney(basicPay));
        
        const totalGross = parseFloat(emp.gross_pay);
        const otPay = Math.max(0, totalGross - basicPay); 
        this.setTxt('psOtHrs', emp.ot_hours || '0');
        this.setTxt('psOtPay', '₱' + this.formatMoney(otPay));
        
        this.setTxt('psTotalGross', '₱' + this.formatMoney(totalGross));
        
        const ded = parseFloat(emp.deductions);
        this.setTxt('psDeductionVal', '-₱' + this.formatMoney(ded));
        this.setTxt('psTotalDed', '-₱' + this.formatMoney(ded));
        this.setTxt('psNet', '₱' + this.formatMoney(emp.net_pay));

        // 2. SHOW MODAL LOGIC (FIXED)
        const modal = document.getElementById('payslipModal');
        if (modal) {
            modal.classList.remove('hidden');
            
            // Add slight delay to allow display:block to render before adding opacity class
            setTimeout(() => {
                const content = modal.querySelector('.modal-content');
                if(content) content.classList.add('modal-open');
            }, 10);
            
            document.body.classList.add('printing-payslip');
        }

        if(typeof lucide !== 'undefined') lucide.createIcons();
    },

    closePayslip() {
        const modal = document.getElementById('payslipModal');
        if (modal) {
            // 1. Remove animation class first
            const content = modal.querySelector('.modal-content');
            if(content) content.classList.remove('modal-open');
            
            // 2. Wait for transition (200ms) then hide wrapper
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
        }
        document.body.classList.remove('printing-payslip');
    },

    updateStats(totals) {
        this.animateValue('statEmployees', totals.count, '');
        this.animateValue('statGross', totals.gross, '₱');
        this.animateValue('statDeductions', totals.deductions, '₱');
        this.animateValue('statNet', totals.net, '₱');
        document.getElementById('footGross').innerText = '₱' + this.formatMoney(totals.gross);
        document.getElementById('footDeductions').innerText = '-₱' + this.formatMoney(totals.deductions);
        document.getElementById('footNet').innerText = '₱' + this.formatMoney(totals.net);
    },

    formatMoney(amount) {
        return parseFloat(amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    },

    animateValue(id, end, prefix) {
        const obj = document.getElementById(id);
        if(!obj) return;
        obj.innerText = prefix + this.formatMoney(end);
    },
    
    setTxt(id, val) { 
        const el = document.getElementById(id); 
        if(el) el.innerText = val; 
    },

    exportPDF() {
        if (typeof window.jspdf === 'undefined') {
            alert('PDF Library not loaded');
            return;
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'mm', 'a4'); 
        const projSelect = document.getElementById('projectSelector');
        const projName = projSelect ? projSelect.options[projSelect.selectedIndex].text : '';
        const period = document.getElementById('tablePeriodLabel').innerText;

        doc.setFillColor(233, 146, 44);
        doc.rect(0, 0, 297, 20, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(16);
        doc.setFont('helvetica', 'bold');
        doc.text('PAYROLL SUMMARY REPORT', 14, 13);
        
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text(`Project: ${projName}  |  Period: ${period}`, 14, 28);
        doc.setTextColor(0,0,0);

        doc.autoTable({
            html: '#payrollTable',
            startY: 35,
            theme: 'striped',
            headStyles: { fillColor: [55, 65, 81], textColor: 255 },
            footStyles: { fillColor: [233, 146, 44], textColor: 255, fontStyle: 'bold' },
            styles: { fontSize: 8, cellPadding: 2 },
            columns: [0, 1, 2, 3, 4, 5, 6, 7] 
        });

        doc.save(`Payroll_${projName}_${period}.pdf`);
    }
,

    renderPagination(totalItems) {
        const container = document.getElementById('payrollPagination');
        if (!container) return;

        const totalPages = Math.max(1, Math.ceil(totalItems / this.perPage));
        container.innerHTML = '';

        const createBtn = (text, cls, onClick, disabled) => {
            const btn = document.createElement('button');
            btn.className = `px-3 py-1 rounded-md text-sm border ${cls}`;
            btn.innerText = text;
            if (disabled) btn.disabled = true;
            btn.addEventListener('click', onClick);
            return btn;
        };

        // Previous
        const prev = createBtn('Prev', 'bg-white border-gray-200', () => this.goToPage(this.currentPage - 1), this.currentPage === 1);
        container.appendChild(prev);

        // Page numbers (show up to 7 pages with ellipsis)
        const maxButtons = 7;
        let startPage = Math.max(1, this.currentPage - Math.floor(maxButtons / 2));
        let endPage = Math.min(totalPages, startPage + maxButtons - 1);
        if (endPage - startPage + 1 < maxButtons) {
            startPage = Math.max(1, endPage - maxButtons + 1);
        }

        for (let p = startPage; p <= endPage; p++) {
            const isActive = p === this.currentPage;
            const btn = createBtn(p, isActive ? 'bg-[#e9922c] text-white border-[#e9922c]' : 'bg-white border-gray-200', () => this.goToPage(p), false);
            container.appendChild(btn);
        }

        // Next
        const next = createBtn('Next', 'bg-white border-gray-200', () => this.goToPage(this.currentPage + 1), this.currentPage === totalPages);
        container.appendChild(next);
    },

    goToPage(page) {
        const total = this.state.data.length;
        const totalPages = Math.max(1, Math.ceil(total / this.perPage));
        if (page < 1) page = 1;
        if (page > totalPages) page = totalPages;
        this.currentPage = page;
        this.renderTable(this.state.data);
        // scroll to top of table
        const table = document.getElementById('payrollTable');
        if (table) table.scrollIntoView({behavior: 'smooth', block: 'start'});
    }
};