<?php
// Sidebar Navigation Component for ICMIS
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page filename and URI
$current_page = basename($_SERVER['PHP_SELF']);
$current_uri = $_SERVER['REQUEST_URI'];

// ------------------------------------------------------------------
// CONFIGURATION: URL PATHS
// ------------------------------------------------------------------

$root_path = '/icmis/';
$budget_path = '/icmis/modules/budget/';
$procurement_path = '/icmis/modules/procurement/';
$labor_path = '/icmis/modules/labor/';

// ------------------------------------------------------------------
// ACTIVE STATE LOGIC
// ------------------------------------------------------------------

// 1. MAIN DASHBOARD (Default Active Tab)
// Active ONLY if filename is dashboard.php AND we are NOT inside a module folder
$is_main_dashboard = ($current_page === 'dashboard.php' && 
                      strpos($current_uri, '/modules/budget/') === false && 
                      strpos($current_uri, '/modules/procurement/') === false && 
                      strpos($current_uri, '/modules/labor/') === false);

// 2. PROJECT MANAGEMENT
$is_projects = ($current_page === 'projects.php');

// 3. SYSTEM ADMIN
$is_admin = ($current_page === 'admin.php');

// 4. BUDGET MODULE
// Parent is active if URI contains /modules/budget/
$is_budget = strpos($current_uri, '/modules/budget/') !== false;

// Sub-pages Logic (Active = Bold)
$is_budget_dashboard = ($current_page === 'dashboard.php' && $is_budget);
$is_proposals = in_array($current_page, ['proposals.php', 'create_proposal.php', 'edit_proposal.php']);
$is_expenses  = in_array($current_page, ['expenses.php', 'add_expense.php', 'edit_expense.php']);
$is_budget_reports = ($current_page === 'reports.php' && $is_budget);

// 5. PROCUREMENT MODULE
$is_procurement = strpos($current_uri, '/modules/procurement/') !== false;
$is_inventory = ($current_page === 'inventory.php');
$is_po = in_array($current_page, ['orders.php', 'create_order.php', 'edit_order.php']);
$is_stock_in = ($current_page === 'stock_in.php');
$is_stock_out = ($current_page === 'stock_out.php');
$is_suppliers = ($current_page === 'suppliers.php');

// 6. LABOR MODULE
$is_labor = strpos($current_uri, '/modules/labor/') !== false;
$is_labor_dashboard = ($current_page === 'dashboard.php' && $is_labor);
$is_employees = ($current_page === 'employees.php');
$is_attendance = ($current_page === 'attendance.php');
$is_assignments = ($current_page === 'assignments.php');
$is_labor_payroll = ($current_page === 'payroll.php' && $is_labor);
$is_labor_reports = ($current_page === 'reports.php' && $is_labor);
?>
<aside class="w-56 bg-white border-r border-gray-200 flex flex-col h-screen fixed left-0 top-0 overflow-hidden z-50 font-sans">
    <div class="px-4 py-6 border-b border-gray-200 ml-10">
        <div class="flex items-center gap-3 mb-1">
            <img src="<?php echo $root_path; ?>assets/images/nobg_logo.png" alt="ICMIS Logo" class="w-10 h-10 object-contain">
            <h1 class="text-xl font-bold text-gray-900 text-center">ICMIS</h1>
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
                <a href="<?php echo $root_path; ?>projects.php" class="flex items-center gap-3 px-3 py-2 <?php echo $is_projects ? 'text-[#e9922c] bg-orange-50 border-r-4 border-[#e9922c] -mr-3' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg transition-colors duration-200 group">
                    <svg class="w-3.5 h-3.5" style="stroke-width: 1.17;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                    <span class="<?php echo $is_projects ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Project Management</span>
                </a>
            </li>

            <li>
                <button onclick="toggleSubmenu(this, 'budget-submenu')" class="w-full flex items-center gap-3 px-3 py-2 <?php echo $is_budget ? 'text-[#e9922c] bg-orange-50 border-r-4 border-[#e9922c] -mr-3' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg transition-colors duration-200">
                    <svg class="w-3.5 h-3.5" style="stroke-width: 1.17;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <span class="font-semibold flex-1 text-left" style="font-size: 11.75px;">Budgeting & Cost Control</span>
                    <svg class="w-3 h-3 transition-transform duration-200 <?php echo $is_budget ? 'rotate-180' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                
                <ul id="budget-submenu" class="<?php echo $is_budget ? '' : 'hidden'; ?> mt-1 ml-6 space-y-1">
                    <?php $project_param = isset($_SESSION['selected_project_id']) ? '?project_id=' . $_SESSION['selected_project_id'] : ''; ?>
                    <li><a href="<?php echo $budget_path; ?>dashboard.php<?php echo $project_param; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_budget_dashboard ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_budget_dashboard ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Budget Dashboard</span></a></li>
                    <li><a href="<?php echo $budget_path; ?>proposals.php<?php echo $project_param; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_proposals ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_proposals ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Budget Proposals</span></a></li>
                    <li><a href="<?php echo $budget_path; ?>expenses.php<?php echo $project_param; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_expenses ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_expenses ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Expense Tracker</span></a></li>
                    <li><a href="<?php echo $budget_path; ?>reports.php<?php echo $project_param; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_budget_reports ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_budget_reports ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Financial Reports</span></a></li>
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
                    <?php $project_param = isset($_SESSION['selected_project_id']) ? '?project_id=' . $_SESSION['selected_project_id'] : ''; ?>
                    <li><a href="<?php echo $procurement_path; ?>inventory.php<?php echo $project_param; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_inventory ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_inventory ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Inventory Masterlist</span></a></li>
                    <li><a href="<?php echo $procurement_path; ?>orders.php<?php echo $project_param; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_po ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_po ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Purchase Orders</span></a></li>
                    <li><a href="<?php echo $procurement_path; ?>stock_in.php<?php echo $project_param; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_stock_in ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_stock_in ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Stock In</span></a></li>
                    <li><a href="<?php echo $procurement_path; ?>stock_out.php<?php echo $project_param; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_stock_out ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_stock_out ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Stock Out</span></a></li>
                    <li><a href="<?php echo $procurement_path; ?>suppliers.php<?php echo $project_param; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_suppliers ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_suppliers ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Suppliers</span></a></li>
                </ul>
            </li>

            <li>
                <button onclick="toggleSubmenu(this, 'labor-submenu')" class="w-full flex items-center gap-3 px-3 py-2 <?php echo $is_labor ? 'text-[#e9922c] bg-orange-50 border-r-4 border-[#e9922c] -mr-3' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg transition-colors duration-200">
                    <svg class="w-3.5 h-3.5" style="stroke-width: 1.17;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                    <span class="font-semibold flex-1 text-left" style="font-size: 11.75px;">Labor & Workforce</span>
                    <svg class="w-3 h-3 transition-transform duration-200 <?php echo $is_labor ? 'rotate-180' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                
                <ul id="labor-submenu" class="<?php echo $is_labor ? '' : 'hidden'; ?> mt-1 ml-6 space-y-1">
                    <?php $project_param = isset($_SESSION['selected_project_id']) ? '?project_id=' . $_SESSION['selected_project_id'] : ''; ?>
                    <li><a href="<?php echo $labor_path; ?>dashboard.php<?php echo $project_param; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_labor_dashboard ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_labor_dashboard ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Dashboard</span></a></li>
                    <li><a href="<?php echo $labor_path; ?>employees.php<?php echo $project_param; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_employees ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_employees ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Employees</span></a></li>
                    <li><a href="<?php echo $labor_path; ?>attendance.php<?php echo $project_param; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_attendance ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_attendance ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Attendance</span></a></li>
                    <li><a href="<?php echo $labor_path; ?>assignments.php<?php echo $project_param; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_assignments ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_assignments ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Assignments</span></a></li>
                    <li><a href="<?php echo $labor_path; ?>payroll.php<?php echo $project_param; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_labor_payroll ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_labor_payroll ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Payroll</span></a></li>
                    <li><a href="<?php echo $labor_path; ?>reports.php<?php echo $project_param; ?>" class="flex items-center w-full px-3 py-2 <?php echo $is_labor_reports ? 'text-[#e9922c] bg-orange-50' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg"><span class="<?php echo $is_labor_reports ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">Reports</span></a></li>
                </ul>
            </li>

            <li>
                <a href="<?php echo $root_path; ?>admin.php" class="flex items-center gap-3 px-3 py-2 <?php echo $is_admin ? 'text-[#e9922c] bg-orange-50 border-r-4 border-[#e9922c] -mr-3' : 'text-gray-500 hover:bg-gray-50'; ?> rounded-lg transition-colors duration-200 group">
                    <svg class="w-3.5 h-3.5" style="stroke-width: 1.17;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="<?php echo $is_admin ? 'font-bold' : 'font-semibold'; ?>" style="font-size: 11.75px;">System Admin</span>
                </a>
            </li>
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