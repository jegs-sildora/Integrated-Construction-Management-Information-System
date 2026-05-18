/* =========================================
   GLOBAL VARIABLES & INIT
   ========================================= */

// AJAX Toast Function
function showToastAjax(message, type = 'success', persist = false) {
    if (persist) {
        sessionStorage.setItem('pendingToast', JSON.stringify({ message, type }));
        return;
    }
    return fetch('../../includes/toast.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message, type })
    }).then(r => r.json()).then(data => {
        document.getElementById('toast-container').insertAdjacentHTML('beforeend', data.html);
        setTimeout(() => dismissToast(data.id), 4000);
    }).catch(err => {
        console.error('Toast error:', err);
        // Fallback: show alert if toast fails
        alert(message);
    });
}

document.addEventListener("DOMContentLoaded", () => {
    // 1. Load History Table
    fetchStockHistory();
    // 2. Setup Event Listeners
    setupEventListeners();
});

/* =========================================
   1. SETUP LISTENERS
   ========================================= */
function setupEventListeners() {
    // Dropdown Change: Fetch Items
    const poSelect = document.getElementById("stk_po_select");
    if(poSelect) {
        poSelect.addEventListener("change", function() {
            const poId = this.value;
            if(poId) fetchOrderDetails(poId);
        });
    }

    // Check All Checkbox
    const checkAll = document.getElementById("check_all_items");
    if(checkAll) {
        checkAll.addEventListener("change", function() {
            const checkboxes = document.querySelectorAll(".item-checkbox");
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
                toggleRowInput(cb);
            });
        });
    }

    // Confirm Button
    const confirmBtn = document.getElementById("confirm_receive_btn");
    if(confirmBtn) {
        confirmBtn.addEventListener("click", submitStockIn);
    }
}

/* =========================================
   2. FETCH STOCK IN HISTORY (Main Dashboard Table)
   ========================================= */
function fetchStockHistory() {
    fetch('php/fetch_stockin.php') 
    .then(response => response.json())
    .then(data => {
        const tbody = document.getElementById("stockin-table-body");
        if(!tbody) return;
        
        tbody.innerHTML = "";

        if (!data || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-8 text-slate-400 italic">No receiving records found.</td></tr>`;
            return;
        }

        // Ensure newest records appear first (by stock_in_id)
        if (Array.isArray(data)) {
            data.sort((a,b) => (b.stock_in_id || 0) - (a.stock_in_id || 0));
        }

        data.forEach(item => {
            const dateDisplay = item.date_received ? item.date_received : (item.date_received_raw ? new Date(item.date_received_raw).toLocaleDateString() : '-');
            
            let row = `
                <tr class="hover:bg-slate-50 border-b border-slate-100 last:border-b-0 transition-colors">
                    <td class="px-6 py-4 font-mono text-xs text-slate-500 text-center">${item.po_reference || 'N/A'}</td>
                    <td class="px-6 py-4 font-bold text-navy-dark text-sm text-center">${item.item_name}</td>
                    <td class="px-6 py-4 text-green-600 font-bold text-sm text-center">+${parseFloat(item.quantity_received).toLocaleString()}</td>
                    <td class="px-6 py-4 text-slate-500 text-sm text-center">${dateDisplay}</td>
                    <td class="px-6 py-4 text-center">
                        <button class="text-gray-400 hover:text-amber-600" onclick="openEditStockModal(${item.stock_in_id}, '${(item.po_reference||'').replace(/'/g, "\\'")}', '${(item.item_name||'').replace(/'/g, "\\'")}', ${parseFloat(item.quantity_received)}, '${item.date_received_raw || ''}')"><i class="fa-solid fa-pen-to-square"></i></button>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    })
    .catch(error => console.error("Fetch History Error:", error));
}

/* =========================================
   5. EDIT MODAL HANDLERS
   ========================================= */
function openEditStockModal(stockId, poRef, itemName, qty, dateRaw) {
    document.getElementById('edit_stock_id').value = stockId;
    document.getElementById('edit_po_ref').value = poRef || '';
    document.getElementById('edit_item_name').value = itemName || '';
    document.getElementById('edit_qty_received').value = qty || 0;
    // dateRaw may be formatted; try to set ISO date if provided
    if (dateRaw) {
        // try parse MMM DD, YYYY -> ISO
        const parsed = new Date(dateRaw);
        if (!isNaN(parsed)) {
            document.getElementById('edit_date_received').value = parsed.toISOString().slice(0,10);
        } else {
            document.getElementById('edit_date_received').value = '';
        }
    } else {
        document.getElementById('edit_date_received').value = '';
    }
    document.getElementById('stockEditModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeEditStockModal() {
    document.getElementById('stockEditModal').classList.add('hidden');
    document.body.style.overflow = '';
}

// Attach update handler
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('update_stock_btn');
    if (btn) btn.addEventListener('click', function() {
        const stockId = document.getElementById('edit_stock_id').value;
        const qty = document.getElementById('edit_qty_received').value;
        const date = document.getElementById('edit_date_received').value;
        if (!stockId) return;
        // Basic validation
        if (parseFloat(qty) <= 0) { showToastAjax('Quantity must be greater than 0', 'error'); return; }

        btn.disabled = true;
        const original = btn.innerText;
        btn.innerHTML = 'Updating...';

        fetch('php/update_stockin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ stock_in_id: stockId, quantity_received: qty, date_received: date })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerText = original;
            if (data.success) {
                showToastAjax('Stock item updated', 'success');
                closeEditStockModal();
                fetchStockHistory();
            } else {
                showToastAjax('Error: ' + (data.message || 'Update failed'), 'error');
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.innerText = original;
            showToastAjax('Server error', 'error');
        });
    });
});

/* =========================================
   3. MODAL LOGIC & PO FETCHING
   ========================================= */

// Open Modal & Load Dropdown
function openStockModal() {
    const modal = document.getElementById('stockModal');
    if(modal) {
        modal.classList.remove('hidden'); 
        document.body.style.overflow = 'hidden'; 
        loadApprovedPOs(); 
    }
}

// Close Modal
function closeStockModal() {
    const modal = document.getElementById('stockModal');
    if(modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        
        // Reset Inputs
        const dropdown = document.getElementById("stk_po_select");
        if(dropdown) dropdown.value = "";
        
        document.getElementById("items_container").classList.add("hidden");
        document.getElementById("po_items_list").innerHTML = "";
    }
}

// Load Dropdown Options
function loadApprovedPOs() {
    const projectIdInput = document.getElementById("selected_project_id");
    const projectId = projectIdInput ? projectIdInput.value : 0;

    fetch(`php/get_approved_pos.php?project_id=${projectId}`)
    .then(res => res.json())
    .then(data => {
        const dropdown = document.getElementById("stk_po_select");
        dropdown.innerHTML = '<option value="" disabled selected>-- Select Approved Purchase Order --</option>';

        if (!data.success || !data.pos || data.pos.length === 0) {
            let option = document.createElement("option");
            option.text = "No Approved POs found";
            option.disabled = true;
            dropdown.appendChild(option);
            return;
        }

        data.pos.forEach(po => {
            let option = document.createElement("option");
            option.value = po.po_id; 
            option.text = `${po.po_reference} - ${po.supplier_name}`;
            dropdown.appendChild(option);
        });
    })
    .catch(err => { console.error(err); showToastAjax('Error loading Purchase Orders', 'error'); });
}

// Fetch Items & Render as TABLE ROWS
function fetchOrderDetails(poId) {
    const container = document.getElementById("items_container");
    const tbody = document.getElementById("po_items_list");

    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-gray-500">Loading items...</td></tr>';
    container.classList.remove("hidden");

    fetch(`php/get_order_details.php?po_id=${poId}`)
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = "";

        if (data.success && data.items.length > 0) {
            data.items.forEach((item, index) => {
                const orderedQty = parseFloat(item.quantity);
                
                // Renders TR elements to match the Table structure
                const row = `
                    <tr class="hover:bg-blue-50 transition-colors border-b border-slate-50 last:border-0">
                        <td class="px-4 py-3 text-center">
                            <input type="checkbox" 
                                   class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer item-checkbox" 
                                   value="${item.id}" 
                                   checked 
                                   onchange="toggleRowInput(this)">
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-800 font-medium item-name-text">
                            ${item.item_name}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 text-right font-mono">
                            ${orderedQty} ${item.unit || 'pcs'}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <input type="number" 
                                id="qty_input_${item.id}" 
                                value="${orderedQty}" 
                                max="${orderedQty}" 
                                min="0" 
                                step="0.01" 
                                class="w-24 p-1.5 text-center text-sm font-bold text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                            >
                        </td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', row);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-red-500">No items found for this PO.</td></tr>`;
        }
    })
    .catch(err => {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-red-500">Error loading details.</td></tr>`;
    });
}

// Toggle Row Inputs
function toggleRowInput(checkbox) {
    const itemId = checkbox.value;
    const input = document.getElementById(`qty_input_${itemId}`);
    const row = checkbox.closest('tr');

    if(checkbox.checked) {
        input.disabled = false;
        input.classList.remove("bg-gray-100", "text-gray-400");
        input.classList.add("bg-white", "text-gray-900");
        row.classList.remove("opacity-50");
    } else {
        input.disabled = true;
        input.classList.add("bg-gray-100", "text-gray-400");
        input.classList.remove("bg-white", "text-gray-900");
        row.classList.add("opacity-50");
    }
}

/* =========================================
   4. SUBMIT STOCK IN
   ========================================= */
function submitStockIn() {
    const poId = document.getElementById("stk_po_select").value;
    const projectId = document.getElementById("selected_project_id").value;
    
    if (!poId) {
        showToastAjax('Please select a Purchase Order first.', 'error');
        return;
    }

    // Collect Data
    let itemsToReceive = [];
    const checkboxes = document.querySelectorAll(".item-checkbox:checked");

    if (checkboxes.length === 0) {
        showToastAjax('Please select at least one item to receive.', 'error');
        return;
    }

    checkboxes.forEach(cb => {
        const itemId = cb.value; // Database ID from purchase_order_items
        const input = document.getElementById(`qty_input_${itemId}`);
        const qty = input.value;
        
        // Get Name from the same row
        const row = cb.closest('tr');
        const itemName = row.querySelector('.item-name-text').innerText.trim();

        if (parseFloat(qty) > 0) {
            itemsToReceive.push({
                item_db_id: itemId, // Matches PHP expected key
                item_name: itemName,
                received_qty: qty   // Matches PHP expected key
            });
        }
    });

    if (itemsToReceive.length === 0) {
        showToastAjax('Receive quantity must be greater than 0.', 'error');
        return;
    }

    // UI Feedback
    const btn = document.getElementById("confirm_receive_btn");
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Processing...`;

    // Send Request
    fetch('php/save_stockin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            project_id: projectId,
            po_id: poId,
            items: itemsToReceive
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            showToastAjax('Stock received successfully!', 'success', true);
            closeStockModal();
            location.reload();
        } else {
            showToastAjax('Error: ' + (data.message || 'Receive failed'), 'error');
            btn.disabled = false;
            btn.innerText = originalText;
        }
    })
    .catch(err => {
        console.error(err);
        showToastAjax('Server connection error.', 'error');
        btn.disabled = false;
        btn.innerText = originalText;
    });
}

// Window Close Event
window.addEventListener('click', function(event) {
    const modal = document.getElementById('stockModal');
    if (event.target === modal) {
        closeStockModal();
    }
});