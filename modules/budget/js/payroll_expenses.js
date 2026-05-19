/**
 * Payroll Expenses Logic
 * Location: /budget/js/payroll_expenses.js
 * Backend: /budget/api/get_payroll_expense_details.php
 */

const PayrollExpenses = {
    // Configuration for endpoints
    api: {
        details: 'api/get_payroll_expense_details.php'
    },

    // Open the modal and fetch details
    async viewDetails(payrollId) {
        const modal = document.getElementById('payrollDetailModal');
        const content = document.getElementById('modal-content');
        const title = document.getElementById('modal-title');
        const subtitle = document.getElementById('modal-subtitle');

        // Show Modal with Loading State
        modal.classList.remove('hidden');
        title.innerText = 'Fetching Details...';
        subtitle.innerText = `Transaction ID: #${payrollId}`;
        content.innerHTML = `
            <div class="animate-pulse flex space-x-4">
                <div class="flex-1 space-y-4 py-1">
                    <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                    <div class="space-y-2">
                        <div class="h-4 bg-gray-200 rounded"></div>
                        <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                    </div>
                </div>
            </div>`;

        try {
            // Fetch Data (read as text first to handle HTML/error responses)
            const res = await fetch(`${this.api.details}?id=${payrollId}`);
            const raw = await res.text();
            let json = null;
            try {
                json = JSON.parse(raw);
            } catch (parseErr) {
                console.error('Payroll details parse error', raw);
                content.innerHTML = `<div class="p-4 text-sm text-red-600">Server response was not valid JSON. See console for raw output.</div><pre class="mt-2 p-3 bg-gray-50 rounded text-xs overflow-auto">${this.escapeHtml(raw)}</pre>`;
                return;
            }

            if (json && json.success) {
                const data = json.data;
                title.innerText = data.employee_name;
                subtitle.innerText = `${data.role} • ${data.period_label}`;

                // Render Breakdown
                content.innerHTML = `
                    <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <p class="text-xs text-gray-500 uppercase font-bold mb-1">Pay Date</p>
                            <p class="font-medium text-gray-900">${data.pay_date}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <p class="text-xs text-gray-500 uppercase font-bold mb-1">Project Phase</p>
                            <p class="font-medium text-gray-900">${data.phase || 'General'}</p>
                        </div>
                    </div>

                    <div class="border-t border-dashed border-gray-200 pt-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-600">Gross Pay</span>
                            <span class="font-mono font-medium text-gray-900">₱${data.gross_pay}</span>
                        </div>
                        
                        <div class="pl-4 border-l-2 border-red-100 my-2 space-y-1">
                            <div class="flex justify-between items-center text-xs text-red-500">
                                <span>SSS</span>
                                <span>-${data.deductions_breakdown.sss}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs text-red-500">
                                <span>PhilHealth</span>
                                <span>-${data.deductions_breakdown.ph}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs text-red-500">
                                <span>Pag-IBIG</span>
                                <span>-${data.deductions_breakdown.pi}</span>
                            </div>
                            <div class="flex justify-between items-center font-bold text-red-600 pt-1">
                                <span>Total Deductions</span>
                                <span>(₱${data.total_deductions})</span>
                            </div>
                        </div>

                        <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-200">
                            <span class="text-lg font-bold text-gray-800">Net Pay Disbursed</span>
                            <span class="text-xl font-black text-[#e9922c] font-mono">₱${data.net_pay}</span>
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = `<p class="text-red-500 text-center py-4">${json.message || 'Error fetching details.'}</p>`;
            }
        } catch (e) {
            console.error(e);
            content.innerHTML = `<p class="text-red-500 text-center py-4">System Error: Could not load data.</p>`;
        }
    },

    closeModal() {
        document.getElementById('payrollDetailModal').classList.add('hidden');
    }
    
    // Helper to escape HTML when rendering raw server output
    
    
    ,
    escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return String(unsafe)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
};