// js/inventory.js

// AJAX Toast Function
function showToastAjax(message, type = 'success', persist = false) {
    if (persist) {
        sessionStorage.setItem('pendingToast', JSON.stringify({ message, type }));
        return;
    }
    fetch('/icmis/includes/toast.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message, type })
    }).then(r => r.json()).then(data => {
        document.getElementById('toast-container').insertAdjacentHTML('beforeend', data.html);
        setTimeout(() => dismissToast(data.id), 4000);
    }).catch(err => console.error('Toast error:', err));
}

document.addEventListener("DOMContentLoaded", () => {
    fetchInventory();
});

function fetchInventory() {
    // Read current project from hidden input (if present) and optional phase_id from URL
    const projectInput = document.getElementById('current_project_id');
    const projectId = projectInput ? projectInput.value : '';
    const pageUrlParams = new URLSearchParams(window.location.search);
    const phaseId = pageUrlParams.get('phase_id') || '';

    let endpoint = 'php/fetch_inventory.php';
    const params = new URLSearchParams();
    if (projectId) params.append('project_id', projectId);
    if (phaseId) params.append('phase_id', phaseId);
    const url = params.toString() ? endpoint + '?' + params.toString() : endpoint;

    fetch(url)
    .then(response => {
        if (!response.ok) throw new Error("HTTP error " + response.status);
        return response.json();
    })
    .then(data => {
        const tbody = document.getElementById("inventory-table-body");
        const totalCountEl = document.getElementById("total-items-count");
        const lowStockCountEl = document.getElementById("low-stock-count");
        const totalValueEl = document.getElementById("total-value-count");
        
        tbody.innerHTML = "";

        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-sm text-gray-500 italic">No inventory records found. Receive items to see them here.</td></tr>';
            if(totalCountEl) totalCountEl.innerText = "0";
            if(lowStockCountEl) lowStockCountEl.innerText = "0";
            if(totalValueEl) totalValueEl.innerText = "₱0.00";
            return;
        }

        let lowStockCounter = 0;
        let grandTotalValuation = 0;

        data.forEach(item => {
            // Values from DB - ensure numeric parsing
            const name = item.item_name || "Unknown Item";
            const category = item.category || "General";
            const qty = parseFloat(item.quantity) || 0;
            const cost = parseFloat(item.unit_cost) || 0;
            const unit = item.unit || "pcs";
            const lastUpdated = item.last_updated || "-";
            
            // Total Value = Unit Cost × Stock Level (per item)
            const totalValue = qty * cost;
            
            // Add to Total Valuation (sum of all item Total Values)
            grandTotalValuation += totalValue;

            // Status Logic
            let statusClass = "bg-green-100 text-green-700";
            let statusText = "In Stock";
            
            if (qty === 0) {
                statusClass = "bg-red-100 text-red-700 border border-red-200";
                statusText = "Out of Stock";
                lowStockCounter++;
            } else if (qty < 20) {
                statusClass = "bg-orange-100 text-orange-700 border border-orange-200";
                statusText = "Low Stock";
                lowStockCounter++;
            }

            // Render Row
            let row = `
                <tr class="hover:bg-slate-50 transition-colors duration-150 border-b border-slate-100 last:border-b-0">
                    <td class="px-6 py-3 text-center">
                        <div class="text-sm font-bold text-navy-dark">${name}</div>
                    </td>
                    <td class="px-6 py-3 text-center">
                        <span class="px-2 py-1 bg-slate-100 text-slate-600 text-xs rounded-md font-bold">${category}</span>
                    </td>
                    <td class="px-6 py-3 text-center">
                        <span class="text-sm font-bold text-slate-800">${qty.toLocaleString()}</span> <span class="text-xs text-slate-500">${unit}</span>
                    </td>
                    <td class="px-6 py-3 text-center text-sm text-slate-600">
                        ₱${cost.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    </td>
                    <td class="px-6 py-3 text-center text-sm font-bold text-slate-700">
                        ₱${totalValue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    </td>
                    <td class="px-6 py-3 text-center text-xs text-slate-500">
                        ${lastUpdated}
                    </td>
                    <td class="px-6 py-3 text-center">
                        <span class="px-2 py-1 inline-flex text-[10px] font-bold uppercase tracking-wide rounded-full ${statusClass}">
                            ${statusText}
                        </span>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });

        // Update Dashboard Counters
        if(totalCountEl) totalCountEl.innerText = data.length;
        if(lowStockCountEl) lowStockCountEl.innerText = lowStockCounter;
        // Total Valuation = sum of all (Unit Cost × Stock Level) for fetched items
        if(totalValueEl) totalValueEl.innerText = '₱' + grandTotalValuation.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    })
    .catch(error => {
        console.error("Error loading inventory:", error);
        showToastAjax("Error loading inventory data", "error");
    });
}