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
    fetchStockOuts();       
    loadInventoryDropdown(); 
});

// Keep mapping between displayed "(CODE) - Name" and employee data (populated server-side)
// `warehousemenMap` is injected on the page where the datalist lives.
const issuedToInput = document.getElementById("stock_issuedTo");
const issuedToIdInput = document.getElementById("stock_issuedTo_id");
if (issuedToInput) {
    issuedToInput.addEventListener('input', function() {
        const key = this.value.trim();
        if (typeof window.warehousemenMap !== 'undefined' && warehousemenMap[key]) {
            const entry = warehousemenMap[key];
            if (issuedToIdInput) issuedToIdInput.value = entry.id || '';
        } else {
            if (issuedToIdInput) issuedToIdInput.value = '';
        }
    });
}

let currentMaxStock = 0.0; 

/* =========================================
   2. FETCH & RENDER HISTORY TABLE
   ========================================= */
function fetchStockOuts() {
    const pidEl = document.getElementById('current_project_id');
    const pid = pidEl ? pidEl.value : (document.getElementById('stock_projectID') ? document.getElementById('stock_projectID').value : 0);
    const url = 'php/fetch_stockout.php' + (pid ? '?project_id=' + encodeURIComponent(pid) : '');
    fetch(url, { credentials: 'same-origin' }) 
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
                <tr class="hover:bg-slate-50 border-b border-slate-100 transition-colors text-center">
                    <td class="text-center px-6 py-4 font-bold text-slate-600 text-sm whitespace-nowrap">${row.stock_out_id}</td>
                    <td class="text-center px-6 py-4 font-bold text-navy-dark text-sm whitespace-nowrap">${row.item_name}</td>
                    <td class="text-center px-6 py-4 font-bold text-red-500 text-sm whitespace-nowrap">-${row.quantity}</td>
                    <td class="text-center px-6 py-4 text-slate-500 text-sm whitespace-nowrap">${row.unit}</td>
                    <td class="text-center px-6 py-4 text-slate-700 text-sm whitespace-nowrap">${row.issued_to}</td>
                    <td class="text-center px-6 py-4 text-slate-500 text-sm whitespace-nowrap">${row.date_issued}</td>
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
    const pidEl = document.getElementById('current_project_id');
    const pid = pidEl ? pidEl.value : (document.getElementById('stock_projectID') ? document.getElementById('stock_projectID').value : 0);
    const url = 'php/fetch_inventory_dropdown.php' + (pid ? '?project_id=' + encodeURIComponent(pid) : '');
    fetch(url, { credentials: 'same-origin' })
    .then(response => response.json())
    .then(data => {
        const dropdown = document.getElementById("stock_itemID");
        if(!dropdown) return;

        dropdown.innerHTML = '<option value="" disabled selected>Select Item (Available Stock)</option>';
        
        if(data.forEach) {
            data.forEach(item => {
                let option = document.createElement("option");
                option.value = item.item_id;
                option.setAttribute("data-max", item.quantity);
                option.setAttribute("data-unit", item.unit);
                option.text = `${item.item_name} (Available: ${item.quantity} ${item.unit})`;
                dropdown.appendChild(option);
            });
        }
    });
}

const itemDropdown = document.getElementById("stock_itemID");
if(itemDropdown) {
    itemDropdown.addEventListener("change", function() {
        let selectedOption = this.options[this.selectedIndex];
        currentMaxStock = parseFloat(selectedOption.getAttribute("data-max")) || 0.0;
        const unit = selectedOption.getAttribute("data-unit") || "units";
        document.getElementById("stock_unit").value = unit;

        const qtyEl = document.getElementById("stock_quantity");
        if (qtyEl) {
            qtyEl.style.borderColor = "#e2e8f0";
            // set max attribute so native validation can pick it up as well
            qtyEl.setAttribute('max', currentMaxStock);
            qtyEl.setAttribute('step', '0.01');
        }
        // reset any previous custom validity
        if (qtyEl && typeof qtyEl.setCustomValidity === 'function') {
            qtyEl.setCustomValidity('');
        }
        // update available display (readonly field)
        const avail = document.getElementById('stock_available_qty');
        if (avail) avail.value = currentMaxStock;
        // re-enable submit and MAX button when changing item
        const submitBtn = document.querySelector('#issueStockForm button[type="submit"]');
        if (submitBtn) submitBtn.disabled = false;
        const maxBtn = document.getElementById('stock_max_btn'); if (maxBtn) maxBtn.disabled = (currentMaxStock <= 0);
    });
}

// MAX button handler: fills qty with currentMaxStock
const maxBtn = document.getElementById('stock_max_btn');
if (maxBtn) {
    maxBtn.addEventListener('click', function() {
        const qtyEl = document.getElementById('stock_quantity');
        const err = document.getElementById('stock_quantity_error');
        if (currentMaxStock <= 0) {
            if (err) err.textContent = 'No available stock to use MAX.';
            return;
        }
        if (qtyEl) {
            // set to max (preserve precision)
            qtyEl.value = Number.isInteger(currentMaxStock) ? String(currentMaxStock) : String(currentMaxStock);
            qtyEl.dispatchEvent(new Event('input', { bubbles: true }));
            qtyEl.focus();
            if (err) err.textContent = '';
        }
    });
}

const qtyInput = document.getElementById("stock_quantity");
if(qtyInput) {
    qtyInput.addEventListener("input", function() {
        let qty = parseFloat(this.value) || 0.0;
        const submitBtn = document.querySelector('#issueStockForm button[type="submit"]');

        if (currentMaxStock > 0 && qty > currentMaxStock) {
                this.style.borderColor = "red";
                if(typeof this.setCustomValidity === 'function') this.setCustomValidity(`Cannot issue ${qty}. Only ${currentMaxStock} in stock.`);
                if(submitBtn) submitBtn.disabled = true;
                const err = document.getElementById('stock_quantity_error'); if(err) err.textContent = `Cannot issue ${qty}. Only ${currentMaxStock} in stock.`;
        } else if (qty <= 0) {
                this.style.borderColor = "red";
                if(typeof this.setCustomValidity === 'function') this.setCustomValidity('Quantity must be greater than 0.');
                if(submitBtn) submitBtn.disabled = true;
                const err = document.getElementById('stock_quantity_error'); if(err) err.textContent = 'Quantity must be greater than 0.';
        } else {
            this.style.borderColor = "#e2e8f0"; 
            if(typeof this.setCustomValidity === 'function') this.setCustomValidity('');
            if(submitBtn) submitBtn.disabled = false;
                const err = document.getElementById('stock_quantity_error'); if(err) err.textContent = '';
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
        let qty = parseFloat(document.getElementById("stock_quantity").value) || 0.0;
        const qtyEl = document.getElementById("stock_quantity");

        if (qty <= 0) {
            showToastAjax('Quantity must be greater than 0.', "error");
            const err = document.getElementById('stock_quantity_error'); if(err) err.textContent = 'Quantity must be greater than 0.';
            if(qtyEl) qtyEl.focus();
            return;
        }

        if (currentMaxStock > 0 && qty > currentMaxStock) {
            showToastAjax(`Cannot issue ${qty}. Only ${currentMaxStock} in stock!`, "error");
            const err = document.getElementById('stock_quantity_error'); if(err) err.textContent = `Cannot issue ${qty}. Only ${currentMaxStock} in stock.`;
            if(qtyEl) qtyEl.focus();
            return; 
        }

        // Ensure we set the selected warehouseman id (if matched) before submitting
        try {
            if (issuedToInput && issuedToIdInput && typeof window.warehousemenMap !== 'undefined') {
                const selectedKey = issuedToInput.value.trim();
                const ent = warehousemenMap[selectedKey];
                issuedToIdInput.value = ent && ent.id ? ent.id : '';
            }
        } catch (e) {
            console.warn('warehousemen mapping not available', e);
        }

        let formData = new FormData(this);

        fetch('php/save_stockout.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                showToastAjax(data.message, "success");
                
                closeIssueModal();
                const err = document.getElementById('stock_quantity_error'); if(err) err.textContent = '';
                const avail = document.getElementById('stock_available_qty'); if (avail) avail.value = '';
                fetchStockOuts();       
                loadInventoryDropdown(); 
            } else {
                showToastAjax("Error: " + data.message, "error");
            }
        })
        .catch(error => {
            console.error(error);
            showToastAjax("Server Error", "error");
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
        
        // reset inline messages and available display
        const avail = document.getElementById('stock_available_qty'); if (avail) avail.value = '';
        const err = document.getElementById('stock_quantity_error'); if (err) err.textContent = '';
        // refresh dropdown options
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