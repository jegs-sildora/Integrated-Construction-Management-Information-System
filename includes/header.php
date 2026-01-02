<?php
// includes/header.php

// 1. Session & User Data
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get User Info from Session (with fallbacks)
$userName = $_SESSION['user_name'] ?? 'Guest User';
$userRole = $_SESSION['user_role'] ?? 'Staff';
$notificationCount = 0; 

// Calculate Initials
$nameParts = explode(' ', $userName);
$userInitials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));

// ------------------------------------------------------------------
// 2. DYNAMIC BREADCRUMB LOGIC
// ------------------------------------------------------------------

$current_uri = $_SERVER['REQUEST_URI'];
$current_file = basename($_SERVER['PHP_SELF']);

// Define defaults if not set by the parent page
if (!isset($pageSection) || !isset($pageTitle)) {
    
    // A. PROCUREMENT MODULE
    if (strpos($current_uri, '/procurement/') !== false) {
        $pageSection = 'Procurement & Inventory';
        
        switch ($current_file) {
            case 'inventory.php':       $pageTitle = 'Inventory Masterlist'; break;
            case 'purchase_orders.php': $pageTitle = 'Purchase Orders'; break;
            case 'stock_in.php':        $pageTitle = 'Stock In'; break;
            case 'stock_out.php':       $pageTitle = 'Stock Out'; break;
            case 'suppliers.php':       $pageTitle = 'Supplier Management'; break;
            case 'create_po.php':       $pageTitle = 'Create Purchase Order'; break;
            default:                    $pageTitle = 'Procurement Dashboard';
        }
    } 
    // B. BUDGET MODULE
    elseif (strpos($current_uri, '/budget/') !== false) {
        $pageSection = 'Budgeting & Cost Control';
        
        switch ($current_file) {
            case 'dashboard.php':       $pageTitle = 'Budget Dashboard'; break;
            case 'proposals.php':       $pageTitle = 'Budget Proposals'; break;
            case 'expenses.php':        $pageTitle = 'Expense Tracker'; break;
            case 'reports.php':         $pageTitle = 'Financial Reports'; break;
            default:                    $pageTitle = 'Budget Overview';
        }
    }
    // C. LABOR MODULE
    elseif (strpos($current_uri, '/labor/') !== false) {
        $pageSection = 'Labor & Workforce';
        
        switch ($current_file) {
            case 'dashboard.php':       $pageTitle = 'Labor Dashboard'; break;
            case 'employees.php':       $pageTitle = 'Employee List'; break;
            case 'attendance.php':      $pageTitle = 'Attendance Record'; break;
            case 'payroll.php':         $pageTitle = 'Payroll Processing'; break;
            default:                    $pageTitle = 'Labor Management';
        }
    }
    // D. MAIN SYSTEM
    elseif ($current_file === 'projects.php') {
        $pageSection = 'Project Management';
        $pageTitle = 'All Projects';
    }
    elseif ($current_file === 'dashboard.php') {
        $pageSection = 'Overview';
        $pageTitle = 'Main Dashboard';
    }
    // E. FALLBACK
    else {
        $pageSection = 'System';
        $pageTitle = 'Page';
    }
}
?>

<header class="bg-white border-b border-gray-200 shadow-sm px-6 py-4 fixed top-0 left-56 right-0 z-10">
    <div class="flex items-center justify-between">
        
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500 font-medium"><?php echo htmlspecialchars($pageSection); ?></span>
            
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            
            <span class="text-sm font-bold text-navy-dark"><?php echo htmlspecialchars($pageTitle); ?></span>
            
            <?php if (!empty($pageSubTitle)): ?>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-sm text-gray-600"><?php echo $pageSubTitle; ?></span>
            <?php endif; ?>
        </div>

        <div class="flex items-center gap-4">
            <button class="p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                <svg class="w-5 h-5 text-gray-500" stroke-width="1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </button>

            <button class="relative p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                <svg class="w-5 h-5 text-gray-500" stroke-width="1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
                <?php if ($notificationCount > 0): ?>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                <?php endif; ?>
            </button>

            <div class="flex items-center gap-3 pl-4 border-l border-gray-100">
                <div class="text-right hidden md:block">
                    <div class="text-sm font-bold text-gray-900 leading-tight"><?php echo htmlspecialchars($userName); ?></div>
                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($userRole); ?></div>
                </div>

                <div class="w-9 h-9 bg-slate-800 text-white rounded-lg flex items-center justify-center shadow-sm">
                    <span class="text-xs font-bold tracking-widest"><?php echo $userInitials; ?></span>
                </div>
            </div>
        </div>
    </div>
</header>

<?php include_once __DIR__ . '/toast.php'; ?>