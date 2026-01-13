/**
 * Reports JavaScript - Procurement Module
 * ICMIS - Integrated Construction Management Information System
 * 
 * Handles report generation and statistics loading for procurement reports
 */

document.addEventListener('DOMContentLoaded', function() {
    loadProcurementStats();
});

/**
 * Load procurement statistics for the dashboard cards
 */
function loadProcurementStats() {
    const projectId = document.getElementById('current_project_id')?.value || 0;
    
    // Fetch inventory stats
    fetchInventoryStats(projectId);
    
    // Fetch purchase order stats
    fetchPurchaseOrderStats(projectId);
}

/**
 * Fetch inventory statistics from the server
 */
function fetchInventoryStats(projectId) {
    let url = 'php/fetch_inventory_dropdown.php';
    if (projectId && parseInt(projectId) > 0) {
        url += `?project_id=${projectId}`;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const items = data.data;
                let totalItems = items.length;
                let lowStock = 0;
                let totalValue = 0;
                
                items.forEach(item => {
                    const stock = parseFloat(item.stock_quantity) || 0;
                    const reorder = parseFloat(item.reorder_level) || 0;
                    const cost = parseFloat(item.unit_cost) || 0;
                    
                    if (stock <= reorder && stock > 0) {
                        lowStock++;
                    }
                    totalValue += stock * cost;
                });
                
                // Update stat cards
                const totalItemsEl = document.getElementById('stat-total-items');
                const lowStockEl = document.getElementById('stat-low-stock');
                const totalValueEl = document.getElementById('stat-total-value');
                
                if (totalItemsEl) totalItemsEl.textContent = totalItems;
                if (lowStockEl) lowStockEl.textContent = lowStock;
                if (totalValueEl) totalValueEl.textContent = formatCurrency(totalValue);
            }
        })
        .catch(error => {
            console.error('Error fetching inventory stats:', error);
        });
}

/**
 * Fetch purchase order statistics from the server
 */
function fetchPurchaseOrderStats(projectId) {
    let url = 'php/fetch_orders.php';
    if (projectId && parseInt(projectId) > 0) {
        url += `?project_id=${projectId}`;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const orders = data.data;
                let pendingOrders = 0;
                
                orders.forEach(order => {
                    const status = (order.status || '').toLowerCase();
                    if (status === 'pending' || status === 'processing') {
                        pendingOrders++;
                    }
                });
                
                // Update stat card
                const pendingOrdersEl = document.getElementById('stat-pending-orders');
                if (pendingOrdersEl) pendingOrdersEl.textContent = pendingOrders;
            }
        })
        .catch(error => {
            console.error('Error fetching purchase order stats:', error);
        });
}

/**
 * Format number as Philippine Peso currency
 */
function formatCurrency(amount) {
    return '₱' + parseFloat(amount).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * Get report title from template type
 */
function getReportTitle(templateType) {
    const titles = {
        'inventory-status': 'Inventory Status Report',
        'purchase-orders': 'Purchase Orders Report',
        'stock-movement': 'Stock Movement Report'
    };
    return titles[templateType] || 'Procurement Report';
}
