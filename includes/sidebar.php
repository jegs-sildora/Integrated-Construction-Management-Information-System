<?php
// sidebar.php

// Sidebar Navigation Component for ICMIS
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page filename and URI
$current_page = basename($_SERVER['PHP_SELF']);
$current_uri = $_SERVER['REQUEST_URI'];

// ------------------------------------------------------------------
// 1. CONTEXT PERSISTENCE LOGIC (ADDED)
// ------------------------------------------------------------------
$active_project_id = 0;

// Priority 1: Check URL
if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
    $active_project_id = intval($_GET['project_id']);
    // Update session to keep it fresh
    $_SESSION['current_project_id'] = $active_project_id;
} 
// Priority 2: Check Session
elseif (isset($_SESSION['current_project_id']) && !empty($_SESSION['current_project_id'])) {
    $active_project_id = $_SESSION['current_project_id'];
}

// Create the Query String to append to links
$project_qs = ($active_project_id > 0) ? '?project_id=' . $active_project_id : '';


// ------------------------------------------------------------------
// CONFIGURATION: URL PATHS
// ------------------------------------------------------------------

$root_path = '/icmis/';
$budget_path = '/icmis/modules/budget/';
$procurement_path = '/icmis/modules/procurement/';
$workforce_path = '/icmis/modules/workforce/';
$project_path = '/icmis/modules/project/'; 
$reports_path = '/icmis/modules/reports/';
$logs_path = '/icmis/modules/admin/'; 
$admin_path = '/icmis/modules/admin/'; 

// ------------------------------------------------------------------
// ACTIVE STATE LOGIC
// ------------------------------------------------------------------

// 1. MAIN DASHBOARD
$is_main_dashboard = ($current_page === 'dashboard.php' && 
                      strpos($current_uri, '/modules/budget/') === false && 
                      strpos($current_uri, '/modules/procurement/') === false && 
                      strpos($current_uri, '/modules/workforce/') === false && 
                      strpos($current_uri, '/modules/project/') === false);

// 2. PROJECT MANAGEMENT
$is_projects = (strpos($current_uri, '/modules/project/') !== false);

// 4. BUDGET MODULE
$is_budget = strpos($current_uri, '/modules/budget/') !== false;
$is_budget_dashboard = ($current_page === 'dashboard.php' && $is_budget);
$is_proposals = in_array($current_page, ['proposals.php', 'create_proposal.php', 'edit_proposal.php']);
$is_expenses  = in_array($current_page, ['expenses.php', 'payroll_expenses.php', 'create_expense.php', 'edit_expense.php']);
$is_budget_reports = ($current_page === 'reports.php' && $is_budget);

// 5. PROCUREMENT MODULE
$is_procurement = strpos($current_uri, '/modules/procurement/') !== false;
$is_inventory = ($current_page === 'inventory.php');
$is_po = in_array($current_page, ['orders.php', 'create_order.php', 'edit_order.php']);
$is_stock_in = ($current_page === 'stock_in.php');
$is_stock_out = ($current_page === 'stock_out.php');
$is_suppliers = ($current_page === 'suppliers.php');

// 6. WORKFORCE MODULE
$is_workforce = strpos($current_uri, '/modules/workforce/') !== false;
$is_workforce_dashboard = ($current_page === 'dashboard.php' && $is_workforce);
$is_employees = in_array($current_page, ['employees.php', 'employee_profile.php', 'edit_employee.php', 'employee_groups.php', 'group_details.php']);
$is_attendance = ($current_page === 'attendance.php');
$is_assignments = ($current_page === 'assignments.php');
$is_workforce_payroll = ($current_page === 'payroll.php' && $is_workforce);
$is_workforce_reports = ($current_page === 'reports.php' && $is_workforce);

// 7. REPORTS MODULE
$is_reports = strpos($current_uri, '/modules/reports/') !== false;

// 8. AUDIT LOGS MODULE (Admin Only)
$is_audit_logs = (strpos($current_uri, '/modules/logs/') !== false || strpos($current_uri, '/modules/admin/audit_logs') !== false);

// 9. TASK MANAGEMENT (removed - navigation consolidated under Project Management)
?>
<aside class="w-56 bg-white border-r border-gray-200 flex flex-col h-screen fixed left-0 top-0 overflow-hidden z-50 font-sans">
    <div class="px-4 py-[1.1rem] border-b border-gray-200 ml-10">
        <div class="flex items-center gap-3 mb-1">
            <img src="<?php echo $root_path; ?>assets/images/nobg_logo.png" alt="ICMIS Logo" class="w-10 h-10 object-contain">
            <h1 class="text-xl font-black text-gray-900 text-center">ICMIS</h1>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 px-3 custom-scrollbar">
        <ul class="space-y-1">
            
            <li>
                <a href="<?php echo $root_path; ?>dashboard.php" class="flex items-center gap-3 px-3 py-2 <?php echo $is_main_dashboard ? 'text-[#e9922c] bg-orange-50 border-r-4 border-[#e9922c] -mr-3' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg transition-colors duration-200 group">
                    <svg class="w-3.5 h-3.5" style="stroke-width: 1.17;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="<?php echo $is_main_dashboard ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Dashboard</span>
                </a>
            </li>

            <li>
                <a href="<?php echo $project_path; ?>projects.php" class="flex items-center gap-3 px-3 py-2 <?php echo $is_projects ? 'text-[#e9922c] bg-orange-50 border-r-4 border-[#e9922c] -mr-3' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg transition-colors duration-200 group">
                    <svg class="w-3.5 h-3.5" style="stroke-width: 1.17;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                    <span class="<?php echo $is_projects ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Project Operations</span>
                </a>
            </li>

            <li>
                <button onclick="toggleSubmenu(this, 'budget-submenu')" class="w-full flex items-center gap-3 px-3 py-2 <?php echo $is_budget ? 'text-[#e9922c] bg-orange-50 border-r-4 border-[#e9922c] -mr-3' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg transition-colors duration-200">
                    <svg class="w-3.5 h-3.5" style="stroke-width: 1.17;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <span class="font-semibold flex-1 text-left" style="font-size: 11.75px;">Budget & Cost Control</span>
                    <svg class="w-3 h-3 transition-transform duration-200 <?php echo $is_budget ? 'rotate-180' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                
                <ul id="budget-submenu" class="<?php echo $is_budget ? '' : 'hidden'; ?> mt-1 ml-6 space-y-1">
                    <li><a href="<?php echo $budget_path; ?>dashboard.php<?php echo $project_qs; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_budget_dashboard ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_budget_dashboard ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Budget Dashboard</span></a></li>
                    <li><a href="<?php echo $budget_path; ?>proposals.php<?php echo $project_qs; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_proposals ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_proposals ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Budget Proposals</span></a></li>
                    <li><a href="<?php echo $budget_path; ?>expenses.php<?php echo $project_qs; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_expenses ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_expenses ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Expense Tracker</span></a></li>
                    <li><a href="<?php echo $budget_path; ?>reports.php<?php echo $project_qs; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_budget_reports ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_budget_reports ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Financial Reports</span></a></li>
                </ul>
            </li>

            <li>
                <button onclick="toggleSubmenu(this, 'procurement-submenu')" class="w-full flex items-center gap-3 px-3 py-2 <?php echo $is_procurement ? 'text-[#e9922c] bg-orange-50 border-r-4 border-[#e9922c] -mr-3' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg transition-colors duration-200">
                    <svg class="w-3.5 h-3.5" style="stroke-width: 1.17;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span class="font-semibold flex-1 text-left" style="font-size: 11.75px;">Procurement & Inventory</span>
                    <svg class="w-3 h-3 transition-transform duration-200 <?php echo $is_procurement ? 'rotate-180' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                
                <ul id="procurement-submenu" class="<?php echo $is_procurement ? '' : 'hidden'; ?> mt-1 ml-6 space-y-1">
                    <li><a href="<?php echo $procurement_path; ?>inventory.php<?php echo $project_qs; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_inventory ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_inventory ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Inventory Masterlist</span></a></li>
                    <li><a href="<?php echo $procurement_path; ?>orders.php<?php echo $project_qs; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_po ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_po ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Purchase Orders</span></a></li>
                    <li><a href="<?php echo $procurement_path; ?>stock_in.php<?php echo $project_qs; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_stock_in ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_stock_in ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Stock In</span></a></li>
                    <li><a href="<?php echo $procurement_path; ?>stock_out.php<?php echo $project_qs; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_stock_out ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_stock_out ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Stock Out</span></a></li>
                    <li><a href="<?php echo $procurement_path; ?>suppliers.php<?php echo $project_qs; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_suppliers ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_suppliers ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Suppliers</span></a></li>
                </ul>
            </li>

            <li>
                <button onclick="toggleSubmenu(this, 'workforce-submenu')" class="w-full flex items-center gap-3 px-3 py-2 <?php echo $is_workforce ? 'text-[#e9922c] bg-orange-50 border-r-4 border-[#e9922c] -mr-3' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg transition-colors duration-200">
                    <svg class="w-3.5 h-3.5" style="stroke-width: 1.17;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                    <span class="font-semibold flex-1 text-left" style="font-size: 11.75px;">Labor & Workforce</span>
                    <svg class="w-3 h-3 transition-transform duration-200 <?php echo $is_workforce ? 'rotate-180' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                
                <ul id="workforce-submenu" class="<?php echo $is_workforce ? '' : 'hidden'; ?> mt-1 ml-6 space-y-1">
                    <li><a href="<?php echo $workforce_path; ?>dashboard.php<?php echo $project_qs; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_workforce_dashboard ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_workforce_dashboard ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Workforce Dashboard</span></a></li>
                    <li><a href="<?php echo $workforce_path; ?>employees.php<?php echo $project_qs; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_employees ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_employees ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Employee Management</span></a></li>
                    <li><a href="<?php echo $workforce_path; ?>attendance.php<?php echo $project_qs; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_attendance ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_attendance ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Attendance Tracker</span></a></li>
                    <li><a href="<?php echo $workforce_path; ?>assignments.php<?php echo $project_qs; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_assignments ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_assignments ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Workforce Assignments</span></a></li>
                    <li><a href="<?php echo $workforce_path; ?>payroll.php<?php echo $project_qs; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_workforce_payroll ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_workforce_payroll ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Payroll Management</span></a></li>
                    <li><a href="<?php echo $workforce_path; ?>reports.php<?php echo $project_qs; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_workforce_reports ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_workforce_reports ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Workforce Reports</span></a></li>
                </ul>
            </li>

            <li>
                <a href="<?php echo $reports_path; ?>index.php" class="flex items-center gap-3 px-3 py-2 <?php echo $is_reports ? 'text-[#e9922c] bg-orange-50 border-r-4 border-[#e9922c] -mr-3' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg transition-colors duration-200 group">
                    <svg class="w-3.5 h-3.5" style="stroke-width: 1.17;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="<?php echo $is_reports ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Reports Center</span>
                </a>
            </li>

            <?php 
            // Audit Logs - Only visible to Admin users
            $user_role = $_SESSION['user_role'] ?? '';
            if (strtolower($user_role) === 'admin'): 
            ?>
            <li>
                <a href="<?php echo $logs_path; ?>audit_logs.php" class="flex items-center gap-3 px-3 py-2 <?php echo $is_audit_logs ? 'text-[#e9922c] bg-orange-50 border-r-4 border-[#e9922c] -mr-3' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg transition-colors duration-200 group">
                    <svg class="w-3.5 h-3.5" style="stroke-width: 1.17;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="<?php echo $is_audit_logs ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Audit Logs</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <script>
    function toggleSubmenu(button, id) {
        const submenu = document.getElementById(id);
        if (submenu) {
            submenu.classList.toggle('hidden');
            
            // Rotate the arrow icon if present
            const arrow = button.querySelector('svg:last-child');
            if (arrow && arrow !== button.querySelector('svg:first-child')) {
                arrow.classList.toggle('rotate-180');
            }
        }
    }
    </script>
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1; 
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #d1d5db; 
            border-radius: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #9ca3af; 
        }
    </style>
</aside>