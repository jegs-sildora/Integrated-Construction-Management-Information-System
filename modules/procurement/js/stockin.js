/* =========================================
   GLOBAL VARIABLES
   ========================================= */
let selectedPOItems = [];

document.addEventListener("DOMContentLoaded", () => {
    fetchStockHistory();
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
            if(poId) fetchPOItems(poId);
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
   1. FETCH STOCK IN HISTORY
   ========================================= */
function fetchStockHistory() {
    fetch('php/fetch_stockin.php') 
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        const tbody = document.getElementById("stockin-table-body");
        tbody.innerHTML = "";

        if (!data || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-slate-400 italic">No receiving records found.</td></tr>`;
            return;
        }

        data.forEach(item => {
            // Generate a Log ID string (e.g., LOG-001)
            const logId = `LOG-${String(item.id).padStart(4, '0')}`;
            
            let row = `
                <tr class="hover:bg-slate-50 border-b border-slate-100 last:border-b-0 transition-colors">
                    <td class="px-6 py-4 font-mono text-xs text-slate-500 text-center">${logId}</td>
                    <td class="px-6 py-4 font-bold text-navy-dark text-sm whitespace-nowrap text-center">
                        ${item.po_reference || '<span class="text-red-400">Unknown PO</span>'}
                    </td>
                    <td class="px-6 py-4 text-slate-700 font-medium text-sm whitespace-nowrap text-center">${item.item_name}</td>
                    <td class="px-6 py-4 text-green-600 font-bold text-sm whitespace-nowrap text-center">+${parseFloat(item.quantity_received).toLocaleString()}</td>
                    <td class="px-6 py-4 text-slate-500 text-sm whitespace-nowrap text-center">${item.date_received}</td>
                    <td class="px-6 py-4 text-slate-600 text-xs font-bold uppercase bg-slate-100 rounded px-2 w-fit whitespace-nowrap text-center">
                        ${item.received_by_name}
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    })
    .catch(error => {
        console.error("Fetch Error:", error);
    });
}

// Load Approved POs for Dropdown
function loadApprovedPOs() {
    fetch('php/get_approved_pos.php')
    .then(res => res.json())
    .then(data => {
        const dropdown = document.getElementById("stk_po_select");
        dropdown.innerHTML = '<option value="" disabled selected>-- Select an Approved Order --</option>';

        if (!data || data.length === 0) {
            let option = document.createElement("option");
            option.text = "No Approved POs found";
            option.disabled = true;
            dropdown.appendChild(option);
            return;
        }

        data.forEach(po => {
            let option = document.createElement("option");
            option.value = po.po_id; 
            option.text = `${po.po_reference} - ${po.supplierName} (${po.order_title})`;
            dropdown.appendChild(option);
        });
    })
    .catch(err => {
        console.error(err);
        showToast("Error loading POs", "error");
    });
}

// Fetch Items for a specific PO
function fetchPOItems(poId) {
    const container = document.getElementById("items_container");
    const tbody = document.getElementById("po_items_list");
    
    // UI Reset
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4"><i class="fa-solid fa-spinner fa-spin text-primary"></i> Loading items...</td></tr>';
    container.classList.remove("hidden");

    fetch(`php/get_po_items.php?po_id=${poId}`)
    .then(res => res.json())
    .then(data => {
        selectedPOItems = data;
        tbody.innerHTML = "";

        if (!data || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-red-500">No items found for this PO.</td></tr>`;
            return;
        }

        data.forEach((item, index) => {
            let row = `
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3 text-center">
                        <input type="checkbox" class="item-checkbox rounded border-slate-300 text-primary focus:ring-primary w-4 h-4 cursor-pointer" 
                               data-index="${index}" checked onchange="toggleRowInput(this)">
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-700 font-medium">
                        ${item.item_name}
                        <input type="hidden" id="item_id_${index}" value="${item.id}">
                        <input type="hidden" id="item_name_${index}" value="${item.item_name}">
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-500 text-right">
                        ${parseFloat(item.quantity).toLocaleString()}
                    </td>
                    <td class="px-4 py-3">
                        <input type="number" id="qty_input_${index}" 
                               value="${item.quantity}" max="${item.quantity}" min="0" step="0.01"
                               class="w-full p-2 text-sm text-right border border-slate-300 rounded focus:ring-1 focus:ring-primary outline-none transition-all">
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    })
    .catch(err => {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-red-500">Failed to load items.</td></tr>`;
    });
}

function toggleRowInput(checkbox) {
    const index = checkbox.dataset.index;
    const input = document.getElementById(`qty_input_${index}`);
    if(checkbox.checked) {
        input.disabled = false;
        input.classList.remove("bg-slate-100", "text-slate-400");
    } else {
        input.disabled = true;
        input.classList.add("bg-slate-100", "text-slate-400");
    }
}

/* =========================================
   3. SUBMIT LOGIC
   ========================================= */
function submitStockIn() {
    const poSelect = document.getElementById("stk_po_select");
    const poId = poSelect.value;
    const userId = document.getElementById("stk_user_id").value;
    
    if (!poId) {
        showToast("Please select a Purchase Order first.", "warning");
        return;
    }

    // Collect Data
    let itemsToReceive = [];
    const checkboxes = document.querySelectorAll(".item-checkbox:checked");

    if (checkboxes.length === 0) {
        showToast("Please select at least one item to receive.", "warning");
        return;
    }

    checkboxes.forEach(cb => {
        const index = cb.dataset.index;
        const itemId = document.getElementById(`item_id_${index}`).value;
        const itemName = document.getElementById(`item_name_${index}`).value;
        const qty = document.getElementById(`qty_input_${index}`).value;

        if (parseFloat(qty) > 0) {
            itemsToReceive.push({
                item_db_id: itemId, // ID from purchase_order_items table
                item_name: itemName,
                received_qty: qty
            });
        }
    });

    if (itemsToReceive.length === 0) {
        showToast("Receive quantity must be greater than 0.", "warning");
        return;
    }

    // Prepare Payload
    const payload = {
        po_id: poId,
        received_by: userId,
        items: itemsToReceive
    };

    // Send Request
    const btn = document.getElementById("confirm_receive_btn");
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = "Processing...";

    fetch('php/save_stockin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            showToast("Stock received successfully!", "success");
            closeStockModal();
            fetchStockHistory();
        } else {
            showToast(data.message || "Failed to receive stock.", "error");
        }
    })
    .catch(err => {
        console.error(err);
        showToast("Server Error", "error");
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerText = originalText;
    });
}

/* =========================================
   4. MODAL UTILITIES
   ========================================= */
function openStockModal() {
    const modal = document.getElementById('stockModal');
    modal.classList.remove('hidden'); 
    loadApprovedPOs(); 
}

function closeStockModal() {
    const modal = document.getElementById('stockModal');
    modal.classList.add('hidden');
    // Reset fields
    document.getElementById("stk_po_select").innerHTML = "";
    document.getElementById("items_container").classList.add("hidden");
    document.getElementById("po_items_list").innerHTML = "";
}

// Close on outside click
window.addEventListener('click', function(event) {
    const modal = document.getElementById('stockModal');
    if (event.target === modal) {
        closeStockModal();
    }
});