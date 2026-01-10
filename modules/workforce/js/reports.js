// Initialize Icons
if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();

// Main Generator Trigger
function generateReport(templateType) {
    const button = event.target.closest('.generate-btn');
    const card = button ? button.closest('.report-template-card') : null;
    const cfg = window.reportsConfig || {};
    const projectId = cfg.projectId || '';
    
    if (!projectId) {
        if (typeof showToast === 'function') showToast('Please select a project first', 'error');
        return;
    }

    // Optional inputs
    let selectedMonth = '';
    if (templateType === 'attendance-summary' && card) {
        selectedMonth = card.querySelector('.month-selector')?.value || '';
    } else if (templateType === 'payroll-report' && card) {
        selectedMonth = card.querySelector('.payroll-month-selector')?.value || '';
    }

    // Construct URL for the new Print Report PHP file
    // Adjust path 'print_report.php' if it is inside api folder or root reports folder
    // Based on previous file structure, assuming it is in same directory or handled by routing
    const url = `./api/print_report.php?project_id=${projectId}&type=${templateType}&month=${selectedMonth}`;
    
    // Open in new tab which will auto-trigger print dialog
    window.open(url, '_blank');

    // Optional: Reload page after a delay to show the new report in the "Recent Reports" list
    setTimeout(() => {
        location.reload();
    }, 2000);
}