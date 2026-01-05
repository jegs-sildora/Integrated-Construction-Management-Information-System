<div id="orderModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fade-in">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden animate-zoom-in flex flex-col">
        
        <div class="bg-gradient-to-r from-navy-dark to-slate-800 px-6 py-5 relative flex-shrink-0">
            <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-blue-400 via-cyan-300 to-blue-400"></div>
            
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center shadow-lg p-1.5">
                        <img src="/icmis/assets/images/nobg_logo.png" alt="ICMIS Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Purchase Order Details</h2>
                        <p class="text-gray-300 text-sm">Procurement Order Summary</p>
                    </div>
                </div>
                <button onclick="closeOrderModal()" class="text-white hover:bg-white/10 rounded-lg p-2 transition-colors cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="overflow-y-auto p-6 space-y-6 flex-1 custom-scrollbar">
            
            <div class="text-center pb-6 border-b-2 border-dashed border-gray-200">
                <div class="inline-block px-4 py-1 bg-blue-50 border border-blue-200 rounded-full mb-2">
                    <span class="text-xs font-bold text-blue-700 tracking-wider">OFFICIAL ORDER DOCUMENT</span>
                </div>
                <h1 id="modal-po-ref" class="text-4xl font-black text-gray-900 mb-1 tracking-tight">PO-XXXX-XXXX</h1>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                    <p class="text-xs text-gray-400 uppercase font-bold mb-2">Order Status</p>
                    <div id="modal-status">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-gray-100 text-gray-600">Loading...</span>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                    <p class="text-xs text-gray-400 uppercase font-bold mb-1">Order Date</p>
                    <p id="modal-date" class="text-lg font-bold text-navy-dark">-</p>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm lg:col-span-2">
                    <p class="text-xs text-gray-400 uppercase font-bold mb-1">Supplier / Vendor</p>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center text-orange-600">
                            <i class="fa-solid fa-truck-fast text-xs"></i>
                        </div>
                        <p id="modal-supplier" class="text-lg font-bold text-navy-dark">-</p>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <div class="bg-blue-50 border-l-4 border-blue-500 rounded-r-lg p-4 flex items-start gap-3">
                    <div class="mt-1 text-blue-500"><i class="fa-solid fa-building"></i></div>
                    <div>
                        <p class="text-xs text-blue-700 uppercase font-bold">Project Allocation</p>
                        <p id="modal-project" class="text-md font-bold text-gray-900">-</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="bg-orange-50 border-l-4 border-orange-500 rounded-r-lg p-4">
                        <p class="text-xs text-orange-700 uppercase font-bold">Order Title</p>
                        <p id="modal-title" class="text-md font-bold text-gray-900">-</p>
                    </div>
                    <div class="bg-purple-50 border-l-4 border-purple-500 rounded-r-lg p-4">
                        <p class="text-xs text-purple-700 uppercase font-bold">Phase / Milestone</p>
                        <p id="modal-phase" class="text-md font-bold text-gray-900">-</p>
                    </div>
                </div>
            </div>

            <div class="border rounded-xl overflow-hidden border-gray-200">
                <div class="bg-gray-50 px-6 py-3 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Items List</h3>
                    <span id="modal-item-count" class="text-xs font-bold bg-white border px-2 py-1 rounded text-gray-600">0 items</span>
                </div>
                
                <table class="w-full text-left">
                    <thead class="bg-white border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase">Item Description</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase text-right">Qty</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase text-right">Unit Cost</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody id="modal-items-container" class="divide-y divide-gray-50 bg-white">
                        </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-right text-sm font-bold text-gray-500 uppercase">Grand Total</td>
                            <td class="px-6 py-4 text-right">
                                <span id="modal-total" class="text-2xl font-black text-primary">₱0.00</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="flex justify-between items-center pt-4 border-t border-gray-100 text-xs text-gray-400">
                <p>Created by: <span id="modal-creator" class="font-bold text-gray-600">-</span></p>
                <button onclick="window.print()" class="flex items-center gap-2 hover:text-navy-dark transition-colors">
                    <i class="fa-solid fa-print"></i> Print Details
                </button>
            </div>
        </div>
    </div>
</div>

<script>
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
        fetch(`php/get_order_details.php?id=${poId}`)
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
</script>