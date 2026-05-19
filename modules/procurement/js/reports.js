/**
 * Reports JavaScript - Procurement Module
 * ICMIS - Integrated Construction Management Information System
 * 
 * Handles report generation and statistics loading for procurement reports
 */

document.addEventListener('DOMContentLoaded', function() {
    loadProcurementStats();
    loadRecentReports();
});

// ============================================
// REPORT GENERATION WITH AJAX LOGGING
// ============================================

/**
 * Generate a report and log it via AJAX
 * @param {string} templateType - The report template type
 * @param {Event} event - The click event (optional)
 */
function generateReport(templateType, event) {
    const button = event ? event.target.closest('.generate-btn') : document.querySelector(`[data-template="${templateType}"] .generate-btn`);
    const projectId = document.getElementById('selected_project_id')?.value || '0';

    if (button) setLoadingState(button);

    // Log the report via AJAX first
    logReportGeneration(templateType, projectId)
        .then(() => {
            // Build URL for the print-based report
            let url = `../reports/print_report.php?type=${encodeURIComponent(templateType)}`;
            if (projectId && projectId !== '0') {
                url += `&project_id=${projectId}`;
            }

            // Open in new tab
            const win = window.open(url, '_blank', 'noopener,noreferrer');
            if (win) {
                win.opener = null;
            }

            // Show success state (this will trigger page reload after 1.5s)
            if (button) showSuccessState(button);
        })
        .catch(error => {
            console.error('Error logging report:', error);
            // Still open the report even if logging fails
            let url = `../reports/print_report.php?type=${encodeURIComponent(templateType)}`;
            if (projectId && projectId !== '0') {
                url += `&project_id=${projectId}`;
            }
            window.open(url, '_blank', 'noopener,noreferrer');
            
            if (button) resetLoadingState(button);
            
            // Still reload to refresh the table
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        });
}

/**
 * Regenerate a report from the recent reports table
 * @param {string} templateType - The report template type
 */
function regenerateReport(templateType) {
    const projectId = document.getElementById('selected_project_id')?.value || '0';

    // Log the regeneration
    logReportGeneration(templateType, projectId)
        .then(() => {
            let url = `../reports/print_report.php?type=${encodeURIComponent(templateType)}`;
            if (projectId && projectId !== '0') {
                url += `&project_id=${projectId}`;
            }

            const win = window.open(url, '_blank', 'noopener,noreferrer');
            if (win) {
                win.opener = null;
            }

            if (typeof showToast === 'function') {
                showToast('Report regenerated successfully!', 'success', true);
            }

            // Reload page after 1.5 seconds to refresh the Recent Reports table
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        })
        .catch(error => {
            console.error('Error logging report:', error);
            // Still open even if logging fails
            let url = `../reports/print_report.php?type=${encodeURIComponent(templateType)}`;
            if (projectId && projectId !== '0') {
                url += `&project_id=${projectId}`;
            }
            window.open(url, '_blank', 'noopener,noreferrer');
            
            // Still reload to refresh the table
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        });
}

/**
 * Log a report generation to the database
 * @param {string} reportType - The report type
 * @param {string|number} projectId - The project ID
 * @returns {Promise}
 */
function logReportGeneration(reportType, projectId) {
    const data = {
        action: 'log',
        report_type: reportType,
        project_id: projectId || 0,
        report_name: getReportTitle(reportType)
    };

    return fetch(`${window.GATEWAY_URL}procurement/reports`, {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${window.AUTH_TOKEN}`
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.warn('Report log warning:', data.message);
        }
        return data;
    });
}

/**
 * Load recent reports via AJAX and update the table
 */
function loadRecentReports() {
    const projectId = document.getElementById('selected_project_id')?.value || '0';
    let url = `${window.GATEWAY_URL}procurement/reports?action=fetch`;
    if (projectId && parseInt(projectId) > 0) {
        url += `&project_id=${projectId}`;
    }

    fetch(url, {
        headers: { 'Authorization': `Bearer ${window.AUTH_TOKEN}` }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateRecentReportsTable(data.data);
            } else {
                console.error('Error fetching reports:', data.message);
            }
        })
        .catch(error => {
            console.error('Error fetching recent reports:', error);
        });
}

/**
 * Update the recent reports table with new data
 * @param {Array} reports - Array of report objects
 */
function updateRecentReportsTable(reports) {
    const tbody = document.querySelector('.recent-reports-table tbody, table tbody');
    if (!tbody) return;

    if (!reports || reports.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-gray-500 bg-white">
                    <div class="flex flex-col items-center gap-2">
                        <i data-lucide="file-x" class="w-10 h-10 text-gray-300"></i>
                        <p class="font-medium">No procurement reports generated yet</p>
                        <p class="text-xs text-gray-400">Use the templates above to generate your first report</p>
                    </div>
                </td>
            </tr>
        `;
        lucide.createIcons();
        return;
    }

    let html = '';
    reports.forEach(report => {
        const iconData = getReportIconData(report.report_type);
        const badgeData = getReportBadgeData(report.report_type);
        
        html += `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 font-medium text-gray-700 flex items-center gap-2">
                    <i data-lucide="${iconData.icon}" class="w-4 h-4 ${iconData.color}"></i>
                    ${escapeHtml(report.report_name || 'Untitled Report')}
                </td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs font-medium rounded-full ${badgeData.classes}">
                        ${badgeData.label}
                    </span>
                </td>
                <td class="px-6 py-4 text-gray-600">${escapeHtml(report.project_name || 'All')}</td>
                <td class="px-6 py-4 text-gray-600">${escapeHtml(report.generated_by || '-')}</td>
                <td class="px-6 py-4 text-gray-500">${report.formatted_date}</td>
                <td class="px-6 py-4 text-right no-print">
                    <button onclick="regenerateReport('${report.report_type}')" class="text-[#e9922c] hover:text-orange-700 font-medium text-sm flex items-center gap-1 ml-auto">
                        <i data-lucide="refresh-cw" class="w-3 h-3"></i> Regenerate
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    lucide.createIcons();
}

/**
 * Get icon data for a report type
 * @param {string} reportType - The report type
 * @returns {Object} Icon name and color class
 */
function getReportIconData(reportType) {
    const icons = {
        'inventory-status': { icon: 'boxes', color: 'text-purple-600' },
        'purchase-orders': { icon: 'file-text', color: 'text-indigo-600' },
        'stock-movement': { icon: 'arrow-left-right', color: 'text-pink-600' }
    };
    return icons[reportType] || { icon: 'file-text', color: 'text-purple-600' };
}

/**
 * Get badge data for a report type
 * @param {string} reportType - The report type
 * @returns {Object} Badge classes and label
 */
function getReportBadgeData(reportType) {
    const badges = {
        'inventory-status': { classes: 'bg-purple-100 text-purple-700', label: 'Inventory Status' },
        'purchase-orders': { classes: 'bg-indigo-100 text-indigo-700', label: 'Purchase Orders' },
        'stock-movement': { classes: 'bg-pink-100 text-pink-700', label: 'Stock Movement' }
    };
    return badges[reportType] || { classes: 'bg-purple-100 text-purple-700', label: ucwords(reportType) };
}

/**
 * Escape HTML special characters
 * @param {string} text - Text to escape
 * @returns {string} Escaped text
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Capitalize first letter of each word
 * @param {string} str - String to transform
 * @returns {string} Transformed string
 */
function ucwords(str) {
    if (!str) return '';
    return str.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

// ============================================
// UI STATE MANAGEMENT
// ============================================

/**
 * Set button to loading state
 * @param {HTMLElement} button - The button element
 */
function setLoadingState(button) {
    if (!button) return;
    button.dataset.originalContent = button.innerHTML;
    button.dataset.originalClasses = button.className;
    button.disabled = true;

    const width = button.offsetWidth;
    button.style.width = `${width}px`;
    button.classList.add('opacity-80', 'cursor-not-allowed');
    button.innerHTML = `<div class="flex items-center justify-center gap-2"><i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i><span>Generating...</span></div>`;
    lucide.createIcons();
}

/**
 * Reset button from loading state
 * @param {HTMLElement} button - The button element
 */
function resetLoadingState(button) {
    if (!button) return;
    button.disabled = false;
    button.style.width = '';
    button.className = button.dataset.originalClasses;
    button.innerHTML = button.dataset.originalContent;
    lucide.createIcons();
}

/**
 * Show success state on button
 * @param {HTMLElement} button - The button element
 */
function showSuccessState(button) {
    if (!button) return;
    button.className = "generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-green-600 text-white font-semibold rounded-lg transition-all duration-300 shadow-sm";
    button.innerHTML = `<div class="flex items-center justify-center gap-2"><i data-lucide="check-circle" class="w-4 h-4"></i><span>Generated</span></div>`;
    lucide.createIcons();

    if (typeof showToast === 'function') {
        showToast('Report generated successfully!', 'success', true);
    }

    // Reload page after 1.5 seconds to refresh the Recent Reports table (same as budget/reports.php)
    setTimeout(() => {
        window.location.reload();
    }, 1500);
}

/**
 * Load procurement statistics for the dashboard cards
 */
function loadProcurementStats() {
    const projectId = document.getElementById('selected_project_id')?.value || 0;
    
    // Fetch inventory stats
    fetchInventoryStats(projectId);
    
    // Fetch purchase order stats
    fetchPurchaseOrderStats(projectId);
}

/**
 * Fetch inventory statistics from the server
 */
function fetchInventoryStats(projectId) {
    let url = `${window.GATEWAY_URL}procurement/inventory`;
    if (projectId && parseInt(projectId) > 0) {
        url += `?project_id=${projectId}`;
    }
    
    fetch(url, {
        headers: { 'Authorization': `Bearer ${window.AUTH_TOKEN}` }
    })
        .then(response => response.json())
        .then(res => {
            const data = res.data || res.inventory || [];
            if (data) {
                const items = data;
                let totalItems = items.length;
                let lowStock = 0;
                let totalValue = 0;
                
                items.forEach(item => {
                    const stock = parseFloat(item.quantity || item.stock_quantity) || 0;
                    const reorder = parseFloat(item.reorder_level) || 20;
                    const cost = parseFloat(item.unit_cost) || 0;
                    
                    if (stock <= reorder && stock >= 0) {
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
    let url = `${window.GATEWAY_URL}procurement/orders`;
    if (projectId && parseInt(projectId) > 0) {
        url += `?project_id=${projectId}`;
    }
    
    fetch(url, {
        headers: { 'Authorization': `Bearer ${window.AUTH_TOKEN}` }
    })
        .then(response => response.json())
        .then(res => {
            const data = res.data || res.orders || [];
            if (data) {
                const orders = data;
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
