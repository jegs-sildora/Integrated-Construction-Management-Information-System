document.addEventListener("DOMContentLoaded", () => {
    fetchInventory();
});

function fetchInventory() {
    // Uses the php/ directory to match your other files
    fetch('php/fetch_inventory.php')
    .then(response => {
        if (!response.ok) throw new Error("HTTP error " + response.status);
        return response.json();
    })
    .then(data => {
        const tbody = document.getElementById("inventory-table-body");
        const totalCountEl = document.getElementById("total-items-count");
        const lowStockCountEl = document.getElementById("low-stock-count");
        
        tbody.innerHTML = "";

        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-sm text-gray-500">No inventory records found.</td></tr>';
            if(totalCountEl) totalCountEl.innerText = "0";
            if(lowStockCountEl) lowStockCountEl.innerText = "0";
            return;
        }

        let lowStockCounter = 0;

        data.forEach(item => {
            // Determine Status Badge Color
            let statusClass = "bg-green-100 text-green-700";
            let statusText = "In Stock";
            
            // Logic for Low Stock
            const qty = parseInt(item.quantity || 0);
            
            if (qty === 0) {
                statusClass = "bg-red-100 text-red-700";
                statusText = "Out of Stock";
                lowStockCounter++;
            } else if (qty < 20) {
                statusClass = "bg-orange-100 text-orange-700";
                statusText = "Low Stock";
                lowStockCounter++;
            }

            let row = `
                <tr class="hover:bg-slate-50 transition-colors duration-150 border-b border-slate-100 last:border-b-0">
                    <td class="px-6 py-3 whitespace-nowrap">
                        <span class="text-sm font-bold text-slate-700">${item.itemName}</span>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        <span class="text-sm font-semibold text-slate-900">${qty}</span>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        <span class="text-sm text-slate-500">${item.unit}</span>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        <span class="text-sm text-slate-400">${item.lastUpdated || '-'}</span>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        <span class="px-2 py-0.5 inline-flex text-[10px] font-bold uppercase tracking-wide rounded-full ${statusClass}">
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
    })
    .catch(error => {
        console.error("Error loading inventory:", error);
        if (typeof showToast === "function") {
            showToast("Error loading inventory data", "error");
        }
    });
}