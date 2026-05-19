let currentOrderId = null;

function openOrderModal(poId) {
    currentOrderId = poId;
    const modal = document.getElementById('orderModal');
    
    // Reset UI
    document.getElementById('modal-items-container').innerHTML = '<tr><td colspan="4" class="text-center py-8 text-gray-400">Loading order details...</td></tr>';
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Lock scroll

    // Fetch Data - ensure path points to the new backend script
    // Assuming current page is modules/procurement/orders.php
    fetch(`${window.GATEWAY_URL}procurement/orders?fetch_id=${poId}`, {
        headers: { 'Authorization': `Bearer ${window.AUTH_TOKEN}` }
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                populateOrderData(data.order, data.items);
            } else {
                alert(data.message || 'Error loading order');
                closeOrderModal();
            }
        })
        .catch(err => {
            console.error(err);
            alert('Connection error');
            closeOrderModal();
        });
}

function populateOrderData(order, items) {
    // 1. Text Fields
    document.getElementById('modal-po-ref').textContent = order.po_reference;
    document.getElementById('modal-date').textContent = new Date(order.order_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});
    document.getElementById('modal-supplier').textContent = order.supplier_name;
    document.getElementById('modal-project').textContent = order.project_name;
    document.getElementById('modal-title').textContent = order.order_title;
    document.getElementById('modal-phase').textContent = order.phase;
    document.getElementById('modal-creator').textContent = order.created_by_name || 'N/A';
    document.getElementById('modal-total').textContent = '₱' + parseFloat(order.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2});

    // 2. Status Badge
    const statusColors = {
        'APPROVED': 'bg-blue-100 text-blue-700 border-blue-200',
        'PENDING': 'bg-amber-100 text-amber-700 border-amber-200',
        'COMPLETED': 'bg-green-100 text-green-700 border-green-200',
        'REJECTED': 'bg-red-100 text-red-700 border-red-200'
    };
    const sClass = statusColors[order.status] || 'bg-gray-100 text-gray-600';
    document.getElementById('modal-status').innerHTML = `
        <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-wide border ${sClass}">
            ${order.status}
        </span>`;

    // 3. Items Loop
    const tbody = document.getElementById('modal-items-container');
    tbody.innerHTML = '';
    document.getElementById('modal-item-count').textContent = items.length + ' items';

    items.forEach(item => {
        const row = `
            <tr class="hover:bg-blue-50/50 transition-colors">
                <td class="px-6 py-3">
                    <p class="text-sm font-bold text-navy-dark">${item.item_name}</p>
                </td>
                <td class="px-6 py-3 text-right text-sm text-gray-600 font-medium">
                    ${parseFloat(item.quantity).toLocaleString()}
                </td>
                <td class="px-6 py-3 text-right text-sm text-gray-600">
                    ₱${parseFloat(item.unit_cost).toLocaleString(undefined, {minimumFractionDigits: 2})}
                </td>
                <td class="px-6 py-3 text-right text-sm font-bold text-navy-dark">
                    ₱${parseFloat(item.total_cost).toLocaleString(undefined, {minimumFractionDigits: 2})}
                </td>
            </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', row);
    });
}

function closeOrderModal() {
    document.getElementById('orderModal').classList.add('hidden');
    document.body.style.overflow = '';
    currentOrderId = null;
}

// Close on Escape or Outside Click
window.addEventListener('keydown', e => { if(e.key === 'Escape') closeOrderModal(); });
document.getElementById('orderModal').addEventListener('click', e => { if(e.target.id === 'orderModal') closeOrderModal(); });