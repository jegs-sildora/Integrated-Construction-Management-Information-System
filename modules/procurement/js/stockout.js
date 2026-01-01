document.addEventListener("DOMContentLoaded", () => {
    fetchStockOuts();       
    loadInventoryDropdown(); 
});

let currentMaxStock = 0; 

/* =========================================
   2. FETCH & RENDER HISTORY TABLE
   ========================================= */
function fetchStockOuts() {
    fetch('php/fetch_stockout.php') 
    .then(response => response.json())
    .then(data => {
        const tbody = document.getElementById("stock-out-table-body");
        if(!tbody) return;
        
        tbody.innerHTML = "";

        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-slate-500">No stock issuance records found.</td></tr>';
            return;
        }

        data.forEach(row => {
            let tr = `
                <tr class="hover:bg-slate-50 border-b border-slate-100 transition-colors">
                    <td class="px-6 py-4 font-bold text-slate-600 text-sm whitespace-nowrap">${row.refNo}</td>
                    <td class="px-6 py-4 font-bold text-navy-dark text-sm whitespace-nowrap">${row.itemName}</td>
                    <td class="px-6 py-4 font-bold text-red-500 text-sm whitespace-nowrap">-${row.quantity}</td>
                    <td class="px-6 py-4 text-slate-500 text-sm whitespace-nowrap">${row.unit}</td>
                    <td class="px-6 py-4 text-slate-700 text-sm whitespace-nowrap">${row.issuedTo}</td>
                    <td class="px-6 py-4 text-slate-500 text-sm whitespace-nowrap">${row.dateIssued}</td>
                    <td class="px-6 py-4 text-slate-400 text-sm whitespace-nowrap italic">${row.notes || '-'}</td>
                </tr>
            `;
            tbody.innerHTML += tr;
        });
    })
    .catch(error => {
        console.error('Error loading history:', error);
    });
}

/* =========================================
   3. LOAD DROPDOWN
   ========================================= */
function loadInventoryDropdown() {
    fetch('php/fetch_inventory_dropdown.php')
    .then(response => response.json())
    .then(data => {
        const dropdown = document.getElementById("stock_itemID");
        if(!dropdown) return;

        dropdown.innerHTML = '<option value="" disabled selected>Select Item (Available Stock)</option>';
        
        if(data.forEach) {
            data.forEach(item => {
                let option = document.createElement("option");
                option.value = item.itemID;
                option.setAttribute("data-max", item.quantity);
                option.setAttribute("data-unit", item.unit);
                option.text = `${item.itemName} (Available: ${item.quantity} ${item.unit})`;
                dropdown.appendChild(option);
            });
        }
    });
}

const itemDropdown = document.getElementById("stock_itemID");
if(itemDropdown) {
    itemDropdown.addEventListener("change", function() {
        let selectedOption = this.options[this.selectedIndex];
        currentMaxStock = parseInt(selectedOption.getAttribute("data-max")) || 0;
        document.getElementById("stock_unit").value = selectedOption.getAttribute("data-unit") || "units";
        document.getElementById("stock_quantity").style.borderColor = "#e2e8f0";
    });
}

const qtyInput = document.getElementById("stock_quantity");
if(qtyInput) {
    qtyInput.addEventListener("input", function() {
        let qty = parseInt(this.value) || 0;
        if (currentMaxStock > 0 && qty > currentMaxStock) {
            // Optional: Simple inline warning if global toast isn't available
            this.style.borderColor = "red";
        } else {
            this.style.borderColor = "#e2e8f0"; 
        }
    });
}

/* =========================================
   4. SAVE STOCK OUT
   ========================================= */
const form = document.getElementById("issueStockForm");
if(form) {
    form.addEventListener("submit", function(event) {
        event.preventDefault();
        let qty = parseInt(document.getElementById("stock_quantity").value);
        
        if (qty > currentMaxStock) {
            if(typeof showToast === 'function') showToast(`Cannot issue ${qty}. Only ${currentMaxStock} in stock!`, "error");
            else alert(`Cannot issue ${qty}. Only ${currentMaxStock} in stock!`);
            return; 
        }

        let formData = new FormData(this);

        fetch('php/save_stockout.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                if(typeof showToast === 'function') showToast(data.message, "success");
                else alert(data.message);
                
                closeIssueModal();
                fetchStockOuts();       
                loadInventoryDropdown(); 
            } else {
                if(typeof showToast === 'function') showToast("Error: " + data.message, "error");
                else alert("Error: " + data.message);
            }
        })
        .catch(error => {
            console.error(error);
            if(typeof showToast === 'function') showToast("Server Error", "error");
        });
    });
}

/* =========================================
   5. MODAL LOGIC (Force Open/Close)
   ========================================= */
function openIssueModal() {
    const modal = document.getElementById("issueStockModal");
    if(modal) {
        document.getElementById("issueStockForm").reset();
        
        // Force Display Flex & Remove Hidden
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        loadInventoryDropdown(); 
    }
}

function closeIssueModal() {
    const modal = document.getElementById("issueStockModal");
    if(modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('issueStockModal');
    if (event.target === modal) {
        closeIssueModal();
    }
}