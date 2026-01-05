<?php
// includes/header.php

// 1. Session & User Data
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userName = $_SESSION['user_name'] ?? 'Guest User';
$userRole = $_SESSION['user_role'] ?? 'Staff';
$nameParts = explode(' ', $userName);
$userInitials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));

// ==================================================================
// 2. CONTEXT SWITCHER VISIBILITY LOGIC
// ==================================================================

$current_uri = $_SERVER['REQUEST_URI'];
$current_file = basename($_SERVER['PHP_SELF']);

// Define where the Project Dropdown should appear
$show_project_selector = (
    strpos($current_uri, '/modules/budget/') !== false ||
    strpos($current_uri, '/modules/procurement/') !== false ||
    strpos($current_uri, '/modules/workforce/') !== false ||
    $current_file === 'dashboard.php' // Main Dashboard
);

// Explicitly hide for Project Module
if (strpos($current_uri, '/modules/project/') !== false) {
    $show_project_selector = false;
}

// ==================================================================
// 3. GLOBAL PROJECT CONTEXT LOGIC
// ==================================================================

$header_projects = [];
$header_project_id = 0;

if ($show_project_selector) {
    // FIX: Robust Connection Handling
    // 1. Check if $conn is valid and open
    $db_connection_valid = (isset($conn) && $conn instanceof mysqli && !$conn->connect_error);
    
    if (!$db_connection_valid) {
        // 2. Try to include database.php
        $db_path = __DIR__ . '/../config/database.php';
        if (file_exists($db_path)) {
            require_once $db_path;
        }
    }

    // Handle Context Switch (URL param overrides session)
    if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
        $_SESSION['selected_project_id'] = intval($_GET['project_id']);
    }

    // Get Current ID
    $header_project_id = $_SESSION['selected_project_id'] ?? 0;

    // Fetch Projects for Dropdown
    // Only proceed if we have a valid connection object
    if (isset($conn) && $conn instanceof mysqli) {
        // FIX: CHANGED ORDER BY 'created_at' TO 'project_id' (created_at column does not exist)
        $h_sql = "SELECT project_id, project_code, project_name FROM icmis_projects ORDER BY project_id DESC";
        $h_result = $conn->query($h_sql);

        if ($h_result && $h_result->num_rows > 0) {
            while ($row = $h_result->fetch_assoc()) {
                $header_projects[] = $row;
                // Default to first if 0
                if ($header_project_id == 0) {
                    $header_project_id = $row['project_id'];
                    $_SESSION['selected_project_id'] = $header_project_id;
                }
            }
        }
    }
}

// ==================================================================
// 4. PAGE TITLES & BREADCRUMBS
// ==================================================================

// Define defaults if not set by parent
if (!isset($pageSection) || !isset($pageTitle)) {
    if (strpos($current_uri, '/procurement/') !== false) {
        $pageSection = 'Procurement & Inventory';
        $pageTitle = 'Overview';
    } elseif (strpos($current_uri, '/budget/') !== false) {
        $pageSection = 'Budget';
        $pageTitle = 'Overview';
    } elseif (strpos($current_uri, '/workforce/') !== false) {
        $pageSection = 'Workforce';
        $pageTitle = 'Overview';
    } elseif (strpos($current_uri, '/project/') !== false) {
        $pageSection = 'Projects';
        $pageTitle = 'Management';
    } else {
        $pageSection = 'System';
        $pageTitle = 'Dashboard';
    }
}
?>

<header class="bg-white border-b border-gray-200 shadow-sm px-6 py-4 fixed top-0 left-56 right-0 z-50 h-20 flex items-center justify-between">
    
    <div class="flex items-center gap-4">
        
        <div class="flex flex-col">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider"><?php echo htmlspecialchars($pageSection); ?></span>
            <div class="flex items-center gap-2">
                <span class="text-lg font-bold text-navy-dark"><?php echo htmlspecialchars($pageTitle); ?></span>
                
                <?php if ($show_project_selector): ?>
                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    
                    <div class="relative group">
                        <select onchange="changeHeaderProject(this.value)" class="appearance-none bg-slate-50 border border-gray-200 hover:border-[#e9922c] text-gray-700 text-sm font-semibold rounded-lg pl-3 pr-8 py-1.5 cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#e9922c] focus:border-transparent transition-all shadow-sm">
                            <?php foreach ($header_projects as $proj): ?>
                                <option value="<?php echo $proj['project_id']; ?>" <?php echo ($proj['project_id'] == $header_project_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($proj['project_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>

    <div class="flex items-center gap-4">
        <button class="relative p-2 rounded-lg hover:bg-gray-100 text-gray-500 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
            <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
        </button>

        <div class="relative inline-block text-left">
            <div id="userDropdownTrigger" class="flex items-center gap-3 pl-4 border-l border-gray-200 cursor-pointer hover:opacity-80 transition-opacity">
                <div class="text-right hidden md:block">
                    <div class="text-sm font-bold text-gray-900 leading-tight"><?php echo htmlspecialchars($userName); ?></div>
                    <div class="text-xs text-gray-500 font-medium"><?php echo htmlspecialchars($userRole); ?></div>
                </div>
                <div class="w-10 h-10 bg-slate-800 text-white rounded-xl flex items-center justify-center shadow-md border-2 border-white ring-1 ring-gray-100">
                    <span class="text-sm font-bold tracking-widest"><?php echo $userInitials; ?></span>
                </div>
            </div>
            
            <div id="userDropdownMenu" class="hidden absolute right-0 mt-3 w-48 bg-white border border-gray-100 rounded-xl shadow-xl z-50 animate-fade-in overflow-hidden">
                <div class="py-1">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profile Settings</a>
                    <div class="border-t border-gray-100 my-1"></div>
                    <a href="<?php echo defined('BASE_URL') ? BASE_URL : '/'; ?>modules/auth/api/logout.php" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-red-600 hover:bg-red-50 transition-colors">
                        <i class="fa-solid fa-right-from-bracket"></i> Log Out
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    function changeHeaderProject(id) {
        const url = new URL(window.location.href);
        url.searchParams.set('project_id', id);
        window.location.href = url.toString();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const trigger = document.getElementById('userDropdownTrigger');
        const menu = document.getElementById('userDropdownMenu');
        if (trigger && menu) {
            trigger.addEventListener('click', (e) => { e.stopPropagation(); menu.classList.toggle('hidden'); });
            window.addEventListener('click', (e) => { if (!trigger.contains(e.target)) menu.classList.add('hidden'); });
        }
    });
</script>