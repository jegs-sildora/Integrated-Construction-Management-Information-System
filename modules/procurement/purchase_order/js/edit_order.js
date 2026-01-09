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

// showToast is provided globally by includes/toast.php

// Load existing items from PHP (set by inline script in PHP file)
let orderItems = window.existingOrderItems || [];
let editingItemId = null; // Track which item is being edited

document.addEventListener('DOMContentLoaded', () => {
    renderItems();
    updatePreviews(); 
});

// --- 1. Form & Preview Logic ---

// Update Previews based on selections
function updatePreviews() {
    // Project (use display input if present)
    const projectDisplay = document.getElementById('project_display');
    if (projectDisplay) {
        document.getElementById('preview-project').innerText = projectDisplay.value || 'No project selected';
    } else {
        const projectSelect = document.getElementById('project');
        if (projectSelect && projectSelect.selectedIndex >= 0) {
            const selectedProj = projectSelect.options[projectSelect.selectedIndex];
            document.getElementById('preview-project').innerText = selectedProj.dataset.name || 'No project selected';
        }
    }
    
    // Phase (use display input if present)
    const phaseDisplay = document.getElementById('targetPhase_display');
    const phaseVal = phaseDisplay ? phaseDisplay.value : document.getElementById('targetPhase').value;
    document.getElementById('preview-phase').innerText = phaseVal || 'No phase selected';
    
    // Supplier
    const supVal = document.getElementById('supplier').value;
    document.getElementById('preview-supplier').innerText = supVal || 'No supplier selected';
}

// Attach listeners (only if the elements are interactive selects)
const _projectEl = document.getElementById('project');
if (_projectEl && _projectEl.tagName === 'SELECT') _projectEl.addEventListener('change', updatePreviews);
const _targetEl = document.getElementById('targetPhase');
if (_targetEl && _targetEl.tagName === 'SELECT') _targetEl.addEventListener('change', updatePreviews);
document.getElementById('supplier').addEventListener('input', updatePreviews);

// Auto-fill Item Inputs from datalist options (works with input + datalist)
document.getElementById('item-name').addEventListener('change', function() {
    if (editingItemId === null) {
        const val = this.value;
        const dataList = document.getElementById('item-name-list');
        let foundQty = null, foundPrice = null;
        if (dataList) {
            const opts = dataList.querySelectorAll('option');
            for (let opt of opts) {
                if (opt.value === val) {
                    foundQty = opt.getAttribute('data-qty');
                    foundPrice = opt.getAttribute('data-price');
                    break;
                }
            }
        }

        if (val) {
            document.getElementById('item-qty').value = (foundQty !== null && foundQty !== '') ? foundQty : '';
            document.getElementById('item-price').value = (foundPrice !== null && foundPrice !== '') ? foundPrice : '';
        } else {
            clearItemInputs();
        }
    }
});

// --- 2. Item Management Logic (Add / Edit / Delete) ---

// A. Main Button Click Handler
document.getElementById('add-item-btn').addEventListener('click', () => {
    const nameSelect = document.getElementById('item-name');
    const name = nameSelect.value;
    const qty = parseFloat(document.getElementById('item-qty').value);
    const price = parseFloat(document.getElementById('item-price').value);

    if (!name || isNaN(qty) || isNaN(price) || qty <= 0) {
        showToastAjax('Valid name, quantity, and price are required', 'error');
        return;
    }

    if (editingItemId !== null) {
        // UPDATE EXISTING ITEM
        const index = orderItems.findIndex(i => i.id === editingItemId);
        if (index !== -1) {
            orderItems[index].name = name;
            orderItems[index].qty = qty;
            orderItems[index].price = price;
            orderItems[index].total = qty * price;
            showToastAjax('Item updated successfully', 'success');
        }
        resetFormState();
    } else {
        // ADD NEW ITEM
        const newItem = { id: Date.now(), name, qty, price, total: qty * price };
        orderItems.push(newItem);
    }

    renderItems();
    clearItemInputs();
});

// B. Load Item into Form (Triggered by clicking the row)
function editItem(id) {
    const item = orderItems.find(i => i.id === id);
    if (!item) return;

    // 1. Populate Inputs
    document.getElementById('item-name').value = item.name;
    document.getElementById('item-qty').value = item.qty;
    document.getElementById('item-price').value = item.price;

    // 2. Set Edit State
    editingItemId = id;

    // 3. Update Button UI
    const btn = document.getElementById('add-item-btn');
    btn.innerHTML = '<i class="fa-solid fa-rotate"></i> Update Item';
    btn.classList.remove('bg-navy-dark', 'hover:bg-slate-700');
    btn.classList.add('bg-orange-600', 'hover:bg-orange-700'); // Visual cue for edit mode

    // 4. Scroll to form (optional, good for mobile)
    document.getElementById('item-name').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// C. Render the List
function renderItems() {
    const container = document.getElementById('order-items-container');
    if (orderItems.length === 0) {
        container.innerHTML = '<div class="text-center py-10 text-gray-400 italic text-sm">No items. Add items using the form.</div>';
        document.getElementById('grand-total').innerText = '₱0.00';
        return;
    }

    let html = '';
    let total = 0;
    
    orderItems.forEach(item => {
        total += item.total;
        // Add visual highlight if this is the item currently being edited
        const activeClass = (item.id === editingItemId) ? 'ring-2 ring-orange-500 bg-orange-50' : 'bg-gray-50 border-gray-100 hover:bg-blue-50 cursor-pointer';
        
        html += `
            <div onclick="editItem(${item.id})" class="flex justify-between items-center p-3 rounded-lg border transition-all ${activeClass} animate-fade-in group relative">
                <div class="flex-1">
                    <p class="text-sm font-bold text-gray-900">${item.name}</p>
                    <p class="text-xs text-gray-500">${item.qty} × ₱${item.price.toLocaleString(undefined, {minimumFractionDigits: 2})}</p>
                </div>
                <div class="text-right flex items-center gap-4">
                    <span class="text-sm font-black text-navy-dark">₱${item.total.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                    
                    <button onclick="event.stopPropagation(); removeItem(${item.id})" class="text-gray-400 hover:text-red-600 transition-colors p-1 rounded-full hover:bg-white">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
                
                <div class="absolute inset-0 flex items-center justify-center bg-white/50 opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity">
                    <span class="text-xs font-bold text-blue-600 bg-white px-2 py-1 rounded shadow-sm">Click to Edit</span>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    document.getElementById('grand-total').innerText = '₱' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
}

// D. Remove Item
function removeItem(id) {
    // If we are deleting the item currently being edited, reset the form
    if (id === editingItemId) {
        resetFormState();
        clearItemInputs();
    }
    orderItems = orderItems.filter(i => i.id !== id);
    renderItems();
}

// E. Utilities
function resetFormState() {
    editingItemId = null;
    const btn = document.getElementById('add-item-btn');
    btn.innerHTML = '<i class="fa-solid fa-plus"></i> Add Item';
    btn.classList.add('bg-navy-dark', 'hover:bg-slate-700');
    btn.classList.remove('bg-orange-600', 'hover:bg-orange-700');
    
    // Re-render to remove the active highlight ring
    renderItems();
}

function clearItemInputs() {
    document.getElementById('item-name').value = '';
    document.getElementById('item-qty').value = '';
    document.getElementById('item-price').value = '';
}

// --- 3. Submit Final Update ---
document.getElementById('submit-po-btn').addEventListener('click', function() {
    const btn = this;
    const poId = document.getElementById('po_id').value;
    const projectId = document.getElementById('project').value;
    const phase = document.getElementById('targetPhase').value;
    const supplier = document.getElementById('supplier').value;
    const title = document.getElementById('orderTitle').value;
    const status = document.querySelector('input[name="orderStatus"]:checked').value;

    if (!projectId || !phase || !supplier || orderItems.length === 0) {
        showToastAjax('Please complete all fields and ensure items are added.', 'error');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';

    const payload = {
        po_id: poId,
        project_id: projectId,
        phase: phase,
        supplier: supplier,
        title: title,
        status: status,
        items: orderItems
    };

    fetch('update_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            setTimeout(() => window.location.href = '../orders.php?msg=updated', 500);
        } else {
            showToastAjax('Error: ' + data.message, 'error');
            btn.disabled = false;
            btn.innerText = 'Update Purchase Order';
        }
    })
    .catch(err => {
        console.error(err);
        showToastAjax('Server error', 'error');
        btn.disabled = false;
        btn.innerText = 'Update Purchase Order';
    });
});