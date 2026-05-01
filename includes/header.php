<?php
/**
 * Header component
 *
 * Responsibilities:
 * - Render the top navigation/header area with page title and user info.
 * - Show a project selector when appropriate (controlled by `$show_project_selector`).
 * - Provide a mobile sidebar toggle for small screens.
 *
 * Implementation notes:
 * - User display name is pulled from session when available; a DB lookup is attempted
 *   only when a name is missing and a DB connection can be established.
 * - The project selector uses `$_SESSION['selected_project_id']` and can be overridden
 *   via `project_id` URL parameter.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userName = $_SESSION['user_name'] ?? 'Guest User';
$userRole = $_SESSION['user_role'] ?? 'Staff';

// Ensure header labels are defined to avoid deprecated null warnings
if (!isset($pageSection) || $pageSection === null) {
    $pageSection = 'ICMIS';
}
if (!isset($pageTitle) || $pageTitle === null) {
    $pageTitle = 'Dashboard';
}

// Attempt to hydrate missing user info from session
if (($userName === 'Guest User' || empty($userName)) && !empty($_SESSION['user_id'])) {
    // Info should already be in session from login_process.php
    $userName = $_SESSION['user_name'] ?? $userName;
    $userRole = $_SESSION['user_role'] ?? $userRole;
}

$nameParts = explode(' ', $userName);
$userInitials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));

// Determine when to show the project selector (controlled by page context)
$current_uri = $_SERVER['REQUEST_URI'];
$current_file = basename($_SERVER['PHP_SELF']);
if (!isset($show_project_selector)) {
    $show_project_selector = (
        strpos($current_uri, '/modules/budget/') !== false ||
        strpos($current_uri, '/modules/procurement/') !== false ||
        strpos($current_uri, '/modules/workforce/') !== false ||
        strpos($current_uri, '/modules/reports/') !== false ||
        $current_file === 'dashboard.php'
    );
}

if (strpos($current_uri, '/modules/project/') !== false) {
    $show_project_selector = false;
}

// If selector is enabled, load projects via API Gateway
$header_projects = [];
$header_project_id = 0;
if ($show_project_selector) {
    require_once __DIR__ . '/../core/ApiHelper.php';
    
    if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
        $_SESSION['selected_project_id'] = intval($_GET['project_id']);
    }
    
    try {
        $projRes = ApiHelper::get('project/projects');
        $header_projects = $projRes['data']['projects'] ?? [];
        $header_project_id = $_SESSION['selected_project_id'] ?? 0;

        if ($header_project_id == 0 && !empty($header_projects)) {
            $header_project_id = $header_projects[0]['project_id'];
            $_SESSION['selected_project_id'] = $header_project_id;
        }
    } catch (Exception $e) {
        error_log("Header Project Fetch Error: " . $e->getMessage());
    }
}
?>

<header class="bg-white border-b border-gray-200 shadow-sm px-4 md:px-6 py-4 fixed top-0 left-0 md:left-56 right-0 z-50 h-20 flex items-center justify-between">
    
    <div class="flex items-center gap-4">
        <button id="sidebarToggle" class="md:hidden p-2 rounded-lg hover:bg-slate-100 mr-2" aria-label="Toggle sidebar">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        
        <div class="flex flex-col">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider"><?php echo htmlspecialchars($pageSection); ?></span>
            <div class="flex items-center gap-2">
                <span class="text-lg font-bold text-navy-dark"><?php echo htmlspecialchars($pageTitle); ?></span>
                
                <?php if ($show_project_selector): ?>
                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>

                    <div class="relative group">
                        <?php if (!empty($header_projects)): ?>
                            <select onchange="changeHeaderProject(this.value)" class="appearance-none bg-slate-50 border border-gray-200 hover:border-[#e9922c] text-gray-700 text-sm font-semibold rounded-lg pl-3 pr-8 py-1.5 cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#e9922c] focus:border-transparent transition-all shadow-sm">
                                <?php foreach ($header_projects as $proj): ?>
                                    <option value="<?php echo $proj['project_id']; ?>" <?php echo ($proj['project_id'] == $header_project_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($proj['project_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <select class="appearance-none bg-slate-50 border border-gray-200 text-gray-400 text-sm font-semibold rounded-lg pl-3 pr-8 py-1.5 cursor-not-allowed shadow-sm" disabled>
                                <option selected>No Projects</option>
                            </select>
                        <?php endif; ?>

                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>

    <div class="flex items-center gap-4">

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
                    <a href="<?php echo defined('BASE_URL') ? BASE_URL : '/'; ?>modules/auth/api/logout.php" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-red-600 hover:bg-red-50 transition-colors">
                        <i class="fa-solid fa-right-from-bracket"></i> Log Out
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Mobile backdrop: appears when sidebar is open on small screens. Uses opacity transition for fade. -->
<div id="mobileSidebarBackdrop" class="fixed inset-0 bg-black bg-opacity-40 z-40 transition-opacity duration-300 opacity-0 pointer-events-none md:hidden"></div>

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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('appSidebar');
        const backdrop = document.getElementById('mobileSidebarBackdrop');
        if (toggle && sidebar) {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                // Toggle animated classes for mobile slide-in/out
                if (sidebar.classList.contains('sidebar-open')) {
                    sidebar.classList.remove('sidebar-open');
                    sidebar.classList.add('sidebar-closed');
                    if (backdrop) { backdrop.classList.remove('opacity-100'); backdrop.classList.remove('pointer-events-auto'); backdrop.classList.add('opacity-0'); backdrop.classList.add('pointer-events-none'); }
                    document.body.classList.remove('overflow-hidden');
                } else {
                    sidebar.classList.remove('sidebar-closed');
                    sidebar.classList.add('sidebar-open');
                    if (backdrop) { backdrop.classList.remove('opacity-0'); backdrop.classList.remove('pointer-events-none'); backdrop.classList.add('opacity-100'); backdrop.classList.add('pointer-events-auto'); }
                    document.body.classList.add('overflow-hidden');
                }
            });

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 768 && sidebar.classList.contains('sidebar-open')) {
                    const isClickInside = sidebar.contains(e.target) || toggle.contains(e.target);
                    if (!isClickInside) {
                        sidebar.classList.remove('sidebar-open');
                        sidebar.classList.add('sidebar-closed');
                        if (backdrop) { backdrop.classList.remove('opacity-100'); backdrop.classList.remove('pointer-events-auto'); backdrop.classList.add('opacity-0'); backdrop.classList.add('pointer-events-none'); }
                        document.body.classList.remove('overflow-hidden');
                    }
                }
            });

            // Close sidebar when clicking the backdrop
            if (backdrop) {
                backdrop.addEventListener('click', function(e) {
                    if (sidebar.classList.contains('sidebar-open')) {
                        sidebar.classList.remove('sidebar-open');
                        sidebar.classList.add('sidebar-closed');
                        backdrop.classList.remove('opacity-100');
                        backdrop.classList.remove('pointer-events-auto');
                        backdrop.classList.add('opacity-0');
                        backdrop.classList.add('pointer-events-none');
                        document.body.classList.remove('overflow-hidden');
                    }
                });
            }
        }
    });
</script>