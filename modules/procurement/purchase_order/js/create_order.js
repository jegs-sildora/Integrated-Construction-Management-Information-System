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

// --- AUTO-FILL FROM PROJECT CONTEXT ---
function autoFillFromContext() {
    const selectedProjectId = document.body.getAttribute('data-selected-project');
    const selectedPhaseId = document.body.getAttribute('data-selected-phase');
    
    // Auto-select project
    if (selectedProjectId && selectedProjectId !== '0') {
        const projectSelect = document.getElementById('project');
        projectSelect.value = selectedProjectId;
        
        // Trigger change event to update preview
        const event = new Event('change');
        projectSelect.dispatchEvent(event);
    }
    
    // Auto-select phase
    if (selectedPhaseId && selectedPhaseId !== '0') {
        const phaseSelect = document.getElementById('targetPhase');
        
        // Find option by phase_id data attribute
        const options = phaseSelect.querySelectorAll('option');
        for (let option of options) {
            if (option.getAttribute('data-phase-id') === selectedPhaseId) {
                phaseSelect.value = option.value;
                
                // Trigger change event to update preview
                const event = new Event('change');
                phaseSelect.dispatchEvent(event);
                break;
            }
        }
    }
}

// --- LOAD LINE ITEMS FROM PROPOSAL VIA AJAX ---
document.addEventListener('DOMContentLoaded', function() {
    const proposalSelect = document.getElementById('proposal_select');
    const itemInput = document.getElementById('item-name');
    const itemDatalist = document.getElementById('item-name-list');
    const projectSelect = document.getElementById('project');
    const phaseSelect = document.getElementById('targetPhase');

    if (proposalSelect) {
        proposalSelect.addEventListener('change', function() {
            const pid = this.value;
            if (!pid) return;
            fetch(`/icmis/modules/budget/budget_proposal/get_proposal_details.php?id=${pid}`)
                .then(res => res.json())
                .then(resp => {
                    if (!resp.success) return showToastAjax('Failed to load proposal', 'error');
                    // Populate datalist items
                    if (itemDatalist) {
                        itemDatalist.innerHTML = '';
                        resp.items.forEach(it => {
                            const opt = document.createElement('option');
                            opt.value = it.item_name;
                            opt.setAttribute('data-qty', it.quantity || '');
                            opt.setAttribute('data-price', it.unit_cost || '');
                            itemDatalist.appendChild(opt);
                        });
                    }

                    // Auto-select project and phase from proposal
                    if (resp.proposal && resp.proposal.project_id) {
                        projectSelect.value = resp.proposal.project_id;
                        projectSelect.dispatchEvent(new Event('change'));
                    }
                    if (resp.proposal && resp.proposal.phase_id) {
                        // find option with matching data-phase-id
                        for (let opt of phaseSelect.options) {
                            if (opt.getAttribute('data-phase-id') == resp.proposal.phase_id) {
                                phaseSelect.value = opt.value;
                                phaseSelect.dispatchEvent(new Event('change'));
                                break;
                            }
                        }
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToastAjax('Error loading proposal details', 'error');
                });
        });
    }
});

// --- MAIN LOGIC ---
let orderItems = [];

document.getElementById('project').addEventListener('change', function() {
    const name = this.options[this.selectedIndex].dataset.name;
    document.getElementById('preview-project').innerText = name || 'No project selected';
    document.getElementById('preview-project').classList.toggle('italic', !name);
});

document.getElementById('targetPhase').addEventListener('change', function() {
    const phaseName = this.value;
    const previewPhase = document.getElementById('preview-phase');
    previewPhase.innerText = phaseName || 'No phase selected';
    previewPhase.classList.toggle('italic', !phaseName);
});

document.addEventListener('DOMContentLoaded', function() {
    // Auto-fill from project context
    autoFillFromContext();
    
    const itemInput = document.getElementById('item-name');
    const itemDatalist = document.getElementById('item-name-list');
    const qtyInput = document.getElementById('item-qty');
    const priceInput = document.getElementById('item-price');
    if (itemInput) {
        itemInput.addEventListener('change', function() {
            const val = this.value;
            let approvedQty = null;
            let approvedPrice = null;
            if (itemDatalist) {
                const options = itemDatalist.querySelectorAll('option');
                for (let opt of options) {
                    if (opt.value === val) {
                        approvedQty = opt.getAttribute('data-qty');
                        approvedPrice = opt.getAttribute('data-price');
                        break;
                    }
                }
            }

            if (val) {
                qtyInput.value = (approvedQty !== null && approvedQty !== '') ? Number(approvedQty) : 1;
                priceInput.value = (approvedPrice !== null && approvedPrice !== '') ? Number(approvedPrice) : '';
            } else {
                qtyInput.value = '';
                priceInput.value = '';
            }
        });
    }
});

document.getElementById('supplier').addEventListener('input', function() {
    const name = this.value;
    const previewSupplier = document.getElementById('preview-supplier');
    previewSupplier.innerText = name || 'No supplier selected';
    previewSupplier.classList.toggle('italic', !name);
});

document.getElementById('add-item-btn').addEventListener('click', () => {
    const name = document.getElementById('item-name').value;
    const qty = parseFloat(document.getElementById('item-qty').value);
    const price = parseFloat(document.getElementById('item-price').value);

    if (!name || isNaN(qty) || isNaN(price) || qty <= 0) {
                showToastAjax('Valid name, quantity, and price are required', 'error');
    }

    const item = { id: Date.now(), name, qty, price, total: qty * price };
    orderItems.push(item);
    renderItems();
    
    document.getElementById('item-name').value = '';
    document.getElementById('item-qty').value = '';
    document.getElementById('item-price').value = '';
});

function renderItems() {
    const container = document.getElementById('order-items-container');
    if (orderItems.length === 0) {
        container.innerHTML = '<div class="text-center py-10 text-gray-400 italic text-sm">Add items to build the purchase order.</div>';
        document.getElementById('grand-total').innerText = '₱0.00';
        return;
    }

    let html = '';
    let total = 0;
    orderItems.forEach(item => {
        total += item.total;
        html += `
            <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg border border-gray-100 animate-fade-in">
                <div class="flex-1">
                    <p class="text-sm font-bold text-gray-900">${item.name}</p>
                    <p class="text-xs text-gray-500">${item.qty} units × ₱${item.price.toLocaleString(undefined, {minimumFractionDigits: 2})}</p>
                </div>
                <div class="text-right flex items-center gap-4">
                    <span class="text-sm font-black text-navy-dark">₱${item.total.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                    <button onclick="removeItem(${item.id})" class="text-red-400 hover:text-red-600 transition-colors">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
        `;
    });
    container.innerHTML = html;
    document.getElementById('grand-total').innerText = '₱' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
}

function removeItem(id) {
    orderItems = orderItems.filter(i => i.id !== id);
    renderItems();
}

document.getElementById('submit-po-btn').addEventListener('click', function() {
    const btn = this;
    const projectId = document.getElementById('project').value;
    const phase = document.getElementById('targetPhase').value;
    const supplier = document.getElementById('supplier').value;
    const title = document.getElementById('orderTitle').value;

    if (!projectId || !phase || !supplier || orderItems.length === 0) {
                showToastAjax('Please complete all fields and add items.', 'error');
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';

    const payload = {
        project_id: projectId,
        phase: phase,
        supplier: supplier,
        title: title,
        items: orderItems
    };

    fetch('save_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            setTimeout(() => window.location.href = '../orders.php?msg=created', 500);
        } else {
            showToastAjax('Error: ' + data.message, 'error');
            btn.disabled = false;
            btn.innerText = 'Submit Purchase Order';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToastAjax('Server connection error. Check console for details.', 'error');
        btn.disabled = false;
        btn.innerText = 'Submit Purchase Order';
    });
});